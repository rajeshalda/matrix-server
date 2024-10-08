<?php

namespace XF\Repository;

use XF\Entity\Forum;
use XF\Entity\Thread;
use XF\Entity\User;
use XF\Finder\ThreadFinder;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class ThreadRepository extends Repository
{
	public function findThreadsForForumView(Forum $forum, array $limits = [])
	{
		/** @var ThreadFinder $finder */
		$finder = $this->finder(ThreadFinder::class);
		$finder
			->inForum($forum, $limits)
			->with('full');

		return $finder;
	}

	public function findThreadsForRssFeed(?Forum $forum = null)
	{
		/** @var ThreadFinder $finder */
		$finder = $this->finder(ThreadFinder::class);

		$finder->where('discussion_state', 'visible')
			->setDefaultOrder('last_post_date', 'DESC')
			->where('discussion_type', '!=', 'redirect')
			->with(['Forum', 'User', 'FirstPost']);

		if ($forum)
		{
			$finder->where('node_id', $forum->node_id);
		}
		else
		{
			$finder->where('Forum.find_new', 1)
				->where('last_post_date', '>', $this->getReadMarkingCutOff());
		}

		return $finder;
	}

	/**
	 * @param bool|false $unreadOnly
	 *
	 * @return ThreadFinder
	 */
	public function findThreadsForWatchedList($unreadOnly = false)
	{
		$visitor = \XF::visitor();
		$userId = $visitor->user_id;

		/** @var ThreadFinder $finder */
		$finder = $this->finder(ThreadFinder::class);
		$finder
			->with('fullForum')
			->with('Watch|' . $userId, true)
			->where('discussion_state', 'visible')
			->setDefaultOrder('last_post_date', 'DESC');

		if ($unreadOnly)
		{
			$finder->unreadOnly($userId);
		}

		return $finder;
	}

	public function findThreadsStartedByUser($userId)
	{
		return $this->finder(ThreadFinder::class)
			->with('fullForum')
			->with(['Forum', 'User'])
			->where('user_id', $userId)
			->where('discussion_type', '<>', 'redirect')
			->setDefaultOrder('last_post_date', 'DESC');
	}

	public function findThreadsWithPostsByUser($userId)
	{
		return $this->finder(ThreadFinder::class)
			->with('fullForum')
			->with(['Forum', 'User'])
			->exists('UserPosts|' . $userId)
			->where('discussion_type', '<>', 'redirect')
			->setDefaultOrder('last_post_date', 'DESC');
	}

	public function findThreadsWithNoReplies()
	{
		return $this->finder(ThreadFinder::class)
			->with('fullForum')
			->with(['Forum', 'User'])
			->where('reply_count', 0)
			->where('discussion_type', '<>', 'redirect')
			->where('last_post_date', '>', $this->getReadMarkingCutOff()) // for performance reasons
			->order('last_post_date', 'DESC')
			->indexHint('FORCE', 'last_post_date');
	}

	/**
	 * @return Finder|ThreadFinder
	 */
	public function findLatestThreads()
	{
		return $this->finder(ThreadFinder::class)
			->with(['Forum', 'User'])
			->where('discussion_state', 'visible')
			->where('discussion_type', '<>', 'redirect')
			->order('post_date', 'DESC');
	}

	/**
	 * @return ThreadFinder
	 */
	public function findThreadsWithLatestPosts()
	{
		return $this->finder(ThreadFinder::class)
			->with(['Forum', 'User'])
			->where('Forum.find_new', true)
			->where('discussion_state', 'visible')
			->where('discussion_type', '<>', 'redirect')
			->where('last_post_date', '>', $this->getReadMarkingCutOff())
			->order('last_post_date', 'DESC')
			->indexHint('FORCE', 'last_post_date');
	}

	/**
	 * @return ThreadFinder
	 */
	public function findThreadsWithUnreadPosts($userId = null)
	{
		$threadFinder = $this->findThreadsWithLatestPosts();

		$userId = $userId ?: \XF::visitor()->user_id;

		if (!$userId)
		{
			return $threadFinder;
		}

		return $threadFinder->unreadOnly($userId);
	}

	/**
	 * @param Forum|null $forum If provided, applies forum-specific limits
	 *
	 * @return ThreadFinder
	 */
	public function findThreadsForApi(?Forum $forum = null)
	{
		/** @var ThreadFinder $threadFinder */
		$threadFinder = $this->finder(ThreadFinder::class)
			->with('api')
			->where('discussion_type', '!=', 'redirect');

		if ($forum)
		{
			$limits = [];
			if (\XF::isApiBypassingPermissions())
			{
				$limits['visibility'] = false;
			}

			$threadFinder->inForum($forum, $limits);
		}
		else
		{
			$threadFinder->where('Forum.find_new', 1)
				->setDefaultOrder('last_post_date', 'DESC');

			if (\XF::isApiCheckingPermissions())
			{
				$forums = $this->repository(ForumRepository::class)->getViewableForums();
				$threadFinder->where('node_id', $forums->keys())
					->where('discussion_state', 'visible');
			}
		}

		return $threadFinder;
	}

	public function logThreadView(Thread $thread)
	{
		$this->db()->query("
			-- XFDB=noForceAllWrite
			INSERT INTO xf_thread_view
				(thread_id, total)
			VALUES
				(? , 1)
			ON DUPLICATE KEY UPDATE
				total = total + 1
		", $thread->thread_id);
	}

	public function batchUpdateThreadViews()
	{
		$db = $this->db();

		$db->query("
			UPDATE xf_thread AS t
			INNER JOIN xf_thread_view AS tv ON (t.thread_id = tv.thread_id)
			SET t.view_count = t.view_count + tv.total
		");

		$viewMetrics = $db->fetchAll(
			'SELECT thread.thread_id AS content_id,
					thread.post_date AS content_date,
					thread.node_id AS content_container_id,
					thread_view.total AS view_count
				FROM xf_thread_view AS thread_view
				INNER JOIN xf_thread AS thread
					ON (thread.thread_id = thread_view.thread_id)'
		);
		foreach ($viewMetrics AS &$viewMetric)
		{
			$viewMetric['log_date'] = \XF::$time;
		}

		$activityLogRepo = $this->repository(ActivityLogRepository::class);
		$activityLogRepo->bulkLog('thread', $viewMetrics);

		$db->emptyTable('xf_thread_view');
	}

	public function markThreadReadByUser(Thread $thread, User $user, $newRead = null)
	{
		if (!$user->user_id)
		{
			return false;
		}

		if ($newRead === null)
		{
			$newRead = max(\XF::$time, $thread->last_post_date);
		}

		$cutOff = $this->getReadMarkingCutOff();
		if ($newRead <= $cutOff)
		{
			return false;
		}

		$readDate = $thread->getUserReadDate($user);
		if ($newRead <= $readDate)
		{
			return false;
		}

		$userId = $user->user_id;
		if (!$thread->Read->offsetExists($userId))
		{
			// the record did not exist, insert ignore it in case it has been created
			$operation = 'INSERT IGNORE';
		}
		else if ($readDate <= $cutOff + 60)
		{
			// the record was close to expiration, replace it in case it has been pruned
			$operation = 'REPLACE';
		}
		else
		{
			// the record was not close to expiration, update it normally
			$operation = 'UPDATE';
		}

		if ($operation === 'UPDATE')
		{
			$query = '-- XFDB=noForceAllWrite
				UPDATE xf_thread_read
				SET thread_read_date = ?
				WHERE user_id = ? AND thread_id = ?';
		}
		else
		{
			$query = "-- XFDB=noForceAllWrite
				{$operation} INTO xf_thread_read
					(thread_read_date, user_id, thread_id)
				VALUES
					(?, ?, ?)
			";
		}

		$this->db()->query($query, [
			$newRead,
			$user->user_id,
			$thread->thread_id,
		]);


		if ($newRead < $thread->last_post_date)
		{
			// thread no fully viewed
			return false;
		}

		if ($thread->Forum && !$this->countUnreadThreadsInForumForUser($thread->Forum, $user))
		{
			/** @var ForumRepository $forumRepo */
			$forumRepo = $this->repository(ForumRepository::class);
			$forumRepo->markForumReadByUser($thread->Forum, $user->user_id);
		}

		return true;
	}

	public function markThreadReadByVisitor(Thread $thread, $newRead = null)
	{
		$visitor = \XF::visitor();
		return $this->markThreadReadByUser($thread, $visitor, $newRead);
	}

	public function pruneThreadReadLogs($cutOff = null)
	{
		if ($cutOff === null)
		{
			$cutOff = $this->getReadMarkingCutOff();
		}

		$this->db()->delete('xf_thread_read', 'thread_read_date < ?', $cutOff);
	}

	public function countUnreadThreadsInForumForUser(Forum $forum, User $user)
	{
		$userId = $user->user_id;
		if (!$userId)
		{
			return 0;
		}

		$read = $forum->Read[$userId];
		$cutOff = $this->getReadMarkingCutOff();

		$readDate = $read ? max($read->forum_read_date, $cutOff) : $cutOff;

		$finder = $this->finder(ThreadFinder::class);
		$finder
			->where('node_id', $forum->node_id)
			->where('last_post_date', '>', $readDate)
			->where('discussion_state', 'visible')
			->where('discussion_type', '<>', 'redirect')
			->whereOr(
				["Read|{$userId}.thread_id", null],
				[$finder->expression('%s > %s', 'last_post_date', "Read|{$userId}.thread_read_date")]
			)
			->skipIgnored();

		return $finder->total();
	}

	public function countUnreadThreadsInForum(Forum $forum)
	{
		$visitor = \XF::visitor();
		return $this->countUnreadThreadsInForumForUser($forum, $visitor);
	}

	public function getReadMarkingCutOff()
	{
		return \XF::$time - $this->options()->readMarkingDataLifetime * 86400;
	}

	public function rebuildThreadUserPostCounters($threadId)
	{
		$db = $this->db();

		$db->beginTransaction();
		$db->delete('xf_thread_user_post', 'thread_id = ?', $threadId);
		$db->query("
			INSERT INTO xf_thread_user_post (thread_id, user_id, post_count)
			SELECT thread_id, user_id, COUNT(*)
			FROM xf_post
			WHERE thread_id = ?
				AND message_state = 'visible'
				AND user_id > 0
			GROUP BY user_id
		", $threadId);
		$db->commit();
	}

	public function rebuildThreadPostPositions($threadId)
	{
		$db = $this->db();
		$db->query('SET @position := -1');
		$db->query("
			UPDATE xf_post
			SET position = (@position := IF(message_state = 'visible', @position + 1, GREATEST(@position, 0)))
			WHERE thread_id = ?
			ORDER BY post_date, post_id
		", $threadId);
	}

	public function sendModeratorActionAlert(Thread $thread, $action, $reason = '', array $extra = [])
	{
		if (!$thread->user_id || !$thread->User)
		{
			return false;
		}

		$extra = array_merge([
			'title' => $thread->title,
			'prefix_id' => $thread->prefix_id,
			'link' => $this->app()->router('public')->buildLink('nopath:threads', $thread),
			'reason' => $reason,
		], $extra);

		/** @var UserAlertRepository $alertRepo */
		$alertRepo = $this->repository(UserAlertRepository::class);
		$alertRepo->alert(
			$thread->User,
			0,
			'',
			'user',
			$thread->user_id,
			"thread_{$action}",
			$extra
		);

		return true;
	}

	/**
	 * @param $url
	 * @param null $type
	 * @param null $error
	 *
	 * @return null|Thread
	 */
	public function getThreadFromUrl($url, $type = null, &$error = null)
	{
		$routePath = $this->app()->request()->getRoutePathFromUrl($url, true);
		$routeMatch = $this->app()->router($type)->routeToController($routePath);
		$params = $routeMatch->getParameterBag();

		if (!$params->thread_id)
		{
			$error = \XF::phrase('no_thread_id_could_be_found_from_that_url');
			return null;
		}

		/** @var Thread $thread */
		$thread = $this->app()->find(Thread::class, $params->thread_id);
		if (!$thread)
		{
			$error = \XF::phrase('no_thread_could_be_found_with_id_x', ['thread_id' => $params->thread_id]);
			return null;
		}

		if ($thread->discussion_type == 'redirect')
		{
			$error = \XF::phrase('please_provide_url_of_non_redirect_thread');
			return null;
		}

		return $thread;
	}

	/**
	 * Returns a map of keys -> thread entity columns.
	 *
	 * Printable version of the keys are expected to exist as phrases in the form "forum_sort.$key".
	 * For example: forum_sort.last_post_date
	 *
	 * @param bool $forAdminConfig If true, this is called in the context of the admin configuring a forum default sort
	 *
	 * @return array
	 */
	public function getDefaultThreadListSortOptions($forAdminConfig): array
	{
		$options = [
			'last_post_date' => 'last_post_date',
			'post_date' => 'post_date',
			'title' => 'title',
			'reply_count' => 'reply_count',
			'view_count' => 'view_count',
			'first_post_reaction_score' => 'first_post_reaction_score',
		];

		if ($forAdminConfig)
		{
			unset($options['view_count'], $options['first_post_reaction_score']);
		}

		return $options;
	}

	/**
	 * Returns a map of keys -> thread entity columns.
	 *
	 * Printable version of the keys are expected to exist as phrases in the form "thread_sort.$key".
	 * For example: thread_sort.post_date
	 *
	 * @return array
	 */
	public function getDefaultPostListSortOptions(): array
	{
		return [
			'post_date' => [
				['position', 'ASC'],
				['post_date', 'ASC'],
			],
		];
	}
}
