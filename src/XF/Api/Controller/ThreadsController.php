<?php

namespace XF\Api\Controller;

use XF\Api\ControllerPlugin\ThreadPlugin;
use XF\Api\Mvc\Reply\ApiResult;
use XF\Entity\Forum;
use XF\Entity\Thread;
use XF\Finder\ThreadFinder;
use XF\Mvc\Entity\Entity;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Error;
use XF\Mvc\Reply\Exception;
use XF\Repository\PostRepository;
use XF\Repository\ThreadRepository;
use XF\Service\Thread\CreatorService;

/**
 * @api-group Threads
 */
class ThreadsController extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertApiScopeByRequestMethod('thread');
	}

	/**
	 * @api-desc Gets a list of threads
	 *
	 * @api-in int $page
	 * @api-see XF\Api\ControllerPlugin\Thread::applyThreadListFilters
	 * @api-see XF\Api\ControllerPlugin\Thread::applyThreadListSort
	 *
	 * @api-out Thread[] $threads
	 * @api-out pagination $pagination
	 */
	public function actionGet()
	{
		$page = $this->filterPage();
		$perPage = $this->options()->discussionsPerPage;

		$threadFinder = $this->setupThreadFinder()->limitByPage($page, $perPage);
		$total = $threadFinder->total();

		$this->assertValidApiPage($page, $perPage, $total);

		$threads = $threadFinder->fetch();

		if (\XF::isApiCheckingPermissions())
		{
			// only filtered to the forums we could view -- could still be other conditions
			$threads = $threads->filterViewable();
		}

		return $this->apiResult([
			'threads' => $threads->toApiResults(),
			'pagination' => $this->getPaginationData($threads, $page, $perPage, $total),
		]);
	}

	/**
	 * @param array $filters List of filters that have been applied from input
	 * @param array|null $sort If array, sort that has been applied from input
	 *
	 * @return ThreadFinder
	 */
	protected function setupThreadFinder(&$filters = [], &$sort = null)
	{
		$threadRepo = $this->repository(ThreadRepository::class);
		$threadFinder = $threadRepo->findThreadsForApi();

		/** @var ThreadPlugin $threadPlugin */
		$threadPlugin = $this->plugin(ThreadPlugin::class);

		$filters = $threadPlugin->applyThreadListFilters($threadFinder);

		$sort = $threadPlugin->applyThreadListSort($threadFinder);

		if (!isset($filters['last_days']))
		{
			if (!$sort || ($sort[0] == 'last_post_date' && $sort[1] == 'desc'))
			{
				$threadFinder->where('last_post_date', '>', $threadRepo->getReadMarkingCutOff());
			}
		}

		if ($sort && $sort[0] == 'post_date' && $sort[1] == 'desc' && !$filters)
		{
			// if sorting by post_date without any other filters, MySQL may choose not to use the
			// post_date index and that tends to be very inefficient
			$threadFinder->indexHint('USE', 'post_date');
		}

		return $threadFinder;
	}

	/**
	 * @return ApiResult|Error
	 * @throws Exception
	 *
	 * @api-desc Creates a thread. Thread type data can be set using additional input specific to the target thread type.
	 *
	 * @api-in <req> int $node_id ID of the forum to create the thread in.
	 * @api-in <req> str $title Title of the thread.
	 * @api-in <req> str $message Body of the first post in the thread.
	 * @api-in str $discussion_type The type of thread to create. Specific types may require additional input.
	 * @api-in int $prefix_id ID of the prefix to apply to the thread. If not valid in the selected forum, will be ignored.
	 * @api-in str[] $tags Array of tag names to apply to the thread.
	 * @api-in string $custom_fields[<name>] Value to apply to the custom field with the specified name.
	 * @api-in bool $discussion_open
	 * @api-in bool $sticky
	 * @api-in str $attachment_key API attachment key to upload files. Attachment key context type must be post with context[node_id] set to the ID of the forum this is being posted in.
	 *
	 * @api-out true $success
	 * @api-out Thread $thread
	 *
	 * @api-error no_permission No permission error.
	 */
	public function actionPost()
	{
		$this->assertRequiredApiInput(['node_id', 'title', 'message']);

		$nodeId = $this->filter('node_id', 'uint');

		/** @var Forum $forum */
		$forum = $this->assertViewableApiRecord(Forum::class, $nodeId);

		if (\XF::isApiCheckingPermissions() && !$forum->canCreateThread($error))
		{
			return $this->noPermission($error);
		}

		$creator = $this->setupThreadCreate($forum);

		if (\XF::isApiCheckingPermissions())
		{
			$creator->checkForSpam();
		}

		if (!$creator->validate($errors))
		{
			return $this->error($errors);
		}

		/** @var Thread $thread */
		$thread = $creator->save();
		$this->finalizeThreadCreate($creator);

		return $this->apiSuccess([
			'thread' => $thread->toApiResult(Entity::VERBOSITY_VERBOSE),
		]);
	}

	/**
	 * @return CreatorService
	 */
	protected function setupThreadCreate(Forum $forum)
	{
		$input = $this->filter([
			'title' => 'str',
			'message' => 'str',
			'prefix_id' => 'uint',
			'custom_fields' => 'array',
			'tags' => 'array-str',
			'discussion_open' => '?bool',
			'sticky' => '?bool',
			'index_state' => '?str',
			'attachment_key' => 'str',
			'discussion_type' => 'str',
			'allow_uncreatable_type' => 'bool',
		]);

		$isBypassingPermissions = \XF::isApiBypassingPermissions();

		/** @var CreatorService $creator */
		$creator = $this->service(CreatorService::class, $forum);

		$allowUncreatable = \XF::isApiBypassingPermissions() && $input['allow_uncreatable_type'];
		$creator->setDiscussionTypeAndDataForApi($input['discussion_type'], $this->request, [], $allowUncreatable);

		$creator->setContent($input['title'], $input['message']);
		$creator->setCustomFields($input['custom_fields']);

		if ($input['prefix_id'] && ($isBypassingPermissions || $forum->isPrefixUsable($input['prefix_id'])))
		{
			$creator->setPrefix($input['prefix_id']);
		}

		if ($isBypassingPermissions || $forum->canEditTags())
		{
			$creator->setTags($input['tags']);
		}

		if ($isBypassingPermissions || $forum->canUploadAndManageAttachments())
		{
			$hash = $this->getAttachmentTempHashFromKey($input['attachment_key'], 'post', ['node_id' => $forum->node_id]);
			$creator->setAttachmentHash($hash);
		}

		$thread = $creator->getThread();

		if (isset($input['discussion_open']) && ($isBypassingPermissions || $thread->canLockUnlock()))
		{
			$creator->setDiscussionOpen($input['discussion_open']);
		}
		if (isset($input['sticky']) && ($isBypassingPermissions || $thread->canStickUnstick()))
		{
			$creator->setSticky($input['sticky']);
		}
		if (isset($input['index_state']) && ($isBypassingPermissions || $thread->canEditModeratorFields()))
		{
			$creator->setIndexState($input['index_state']);
		}

		return $creator;
	}

	protected function finalizeThreadCreate(CreatorService $creator)
	{
		$creator->sendNotifications();

		$thread = $creator->getThread();
		$visitor = \XF::visitor();

		if ($visitor->user_id)
		{
			$this->getThreadRepo()->markThreadReadByVisitor($thread, $thread->post_date);
		}
	}

	/**
	 * @return ThreadRepository
	 */
	protected function getThreadRepo()
	{
		return $this->repository(ThreadRepository::class);
	}

	/**
	 * @return PostRepository
	 */
	protected function getPostRepo()
	{
		return $this->repository(PostRepository::class);
	}
}
