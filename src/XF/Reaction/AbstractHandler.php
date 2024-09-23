<?php

namespace XF\Reaction;

use XF\Entity\ReactionContent;
use XF\Entity\User;
use XF\Mvc\Entity\Entity;
use XF\Repository\NewsFeedRepository;
use XF\Repository\UserAlertRepository;

abstract class AbstractHandler
{
	protected $contentType;

	protected $contentCacheFields = [
		'score' => 'reaction_score',
		'counts' => 'reactions',
		'recent' => 'reaction_users',
	];

	public function __construct($contentType)
	{
		$this->contentType = $contentType;
	}

	abstract public function reactionsCounted(Entity $entity);

	public function canViewContent(Entity $entity, &$error = null)
	{
		if (method_exists($entity, 'canView'))
		{
			return $entity->canView($error);
		}

		throw new \LogicException("Could not determine content viewability; please override");
	}

	public function getTemplateName()
	{
		return 'public:reaction_item_' . $this->contentType;
	}

	public function getTemplateData(ReactionContent $reaction, ?Entity $content = null)
	{
		if (!$content)
		{
			$content = $reaction->Content;
		}

		return [
			'reaction' => $reaction,
			'content' => $content,
		];
	}

	public function render(ReactionContent $reaction, ?Entity $content = null)
	{
		if (!$content)
		{
			$content = $reaction->Content;
			if (!$content)
			{
				return '';
			}
		}

		$template = $this->getTemplateName();
		$data = $this->getTemplateData($reaction, $content);

		return \XF::app()->templater()->renderTemplate($template, $data);
	}

	public function isRenderable(ReactionContent $reaction)
	{
		$template = $this->getTemplateName();
		return \XF::app()->templater()->isKnownTemplate($template);
	}

	public function updateContentReactions(Entity $entity, $counts, array $latestReactions)
	{
		$scoreField = $this->contentCacheFields['score'] ?? false;
		$countsField = $this->contentCacheFields['counts'] ?? false;
		$recentField = $this->contentCacheFields['recent'] ?? false;

		if (!$scoreField && !$countsField && !$recentField)
		{
			return;
		}

		if ($scoreField)
		{
			$reactionsCache = \XF::app()->container('reactions');
			$score = 0;
			foreach ($counts AS $reactionId => $count)
			{
				if (!isset($reactionsCache[$reactionId]) || !$reactionsCache[$reactionId]['active'])
				{
					unset($counts[$reactionId]);
					continue;
				}

				$reaction = $reactionsCache[$reactionId];
				$score += $count * $reaction['reaction_score'];
			}
			$entity->$scoreField = $score;
		}
		if ($countsField)
		{
			$entity->$countsField = $counts;
		}
		if ($recentField)
		{
			$entity->$recentField = $latestReactions;
		}

		$entity->save();
	}

	public function updateRecentCacheForUserChange($oldUserId, $newUserId, $oldUsername, $newUsername)
	{
		if (empty($this->contentCacheFields['recent']))
		{
			return;
		}

		$entityType = \XF::app()->getContentTypeEntity($this->contentType, false);
		if (!$entityType)
		{
			return;
		}

		$structure = \XF::em()->getEntityStructure($entityType);

		// note that xf_reaction_content must already be updated
		$oldFind = $this->getUserStringForReactionUsers($oldUserId, $oldUsername);
		$newReplace = $this->getUserStringForReactionUsers($newUserId, $newUsername);

		$recentField = $this->contentCacheFields['recent'];
		$table = $structure->table;
		$primaryKey = $structure->primaryKey;

		\XF::db()->query("
			UPDATE (
				SELECT content_id FROM xf_reaction_content
				WHERE content_type = ?
				AND reaction_user_id = ?
			) AS temp
			INNER JOIN {$table} AS reaction_table ON (reaction_table.`$primaryKey` = temp.content_id)
			SET reaction_table.`{$recentField}` = REPLACE(reaction_table.`{$recentField}`, ?, ?)
			WHERE reaction_table.`{$recentField}` LIKE ?
		", [$this->contentType, $newUserId, $oldFind, $newReplace, '%' . \XF::db()->escapeLike($oldFind) . '%']);
	}

	protected function getUserStringForReactionUsers($userId, $username)
	{
		return substr(json_encode(['user_id' => $userId, 'username' => $username]), 1, -1);
	}

	public function getContentReactionCaches(Entity $entity)
	{
		$countsField = $this->getCountsFieldName();
		$recentField = $this->getRecentFieldName();
		$output = [];

		if ($countsField)
		{
			$output['counts'] = $entity->$countsField;
		}
		if ($recentField)
		{
			$output['recent'] = $entity->$recentField;
		}

		return $output;
	}

	public function sendReactionAlert(User $receiver, User $sender, $contentId, Entity $content, $reactionId)
	{
		$canView = \XF::asVisitor($receiver, function () use ($content)
		{
			return $this->canViewContent($content);
		});
		if (!$canView)
		{
			return false;
		}

		/** @var UserAlertRepository $alertRepo */
		$alertRepo = \XF::repository(UserAlertRepository::class);
		return $alertRepo->alertFromUser(
			$receiver,
			$sender,
			$this->contentType,
			$contentId,
			'reaction',
			['reaction_id' => $reactionId] + $this->getExtraDataForAlertOrFeed($content, 'alert')
		);
	}

	public function removeReactionAlert(ReactionContent $reactionContent)
	{
		/** @var UserAlertRepository $alertRepo */
		$alertRepo = \XF::repository(UserAlertRepository::class);
		$alertRepo->fastDeleteAlertsFromUser($reactionContent->reaction_user_id, $this->contentType, $reactionContent->content_id, 'reaction');
	}

	public function publishReactionNewsFeed(User $sender, $contentId, Entity $content, $reactionId)
	{
		/** @var NewsFeedRepository $newsFeedRepo */
		$newsFeedRepo = \XF::repository(NewsFeedRepository::class);
		$newsFeedRepo->publish(
			$this->contentType,
			$contentId,
			'reaction',
			$sender->user_id,
			$sender->username,
			['reaction_id' => $reactionId] + $this->getExtraDataForAlertOrFeed($content, 'feed')
		);
	}

	public function unpublishReactionNewsFeed(ReactionContent $reactionContent)
	{
		/** @var NewsFeedRepository $newsFeedRepo */
		$newsFeedRepo = \XF::repository(NewsFeedRepository::class);
		$newsFeedRepo->unpublish($this->contentType, $reactionContent->content_id, $reactionContent->reaction_user_id, 'reaction');
	}

	protected function getExtraDataForAlertOrFeed(Entity $content, $context)
	{
		return [];
	}

	public function getContentUserId(Entity $entity)
	{
		if (isset($entity->user_id))
		{
			return $entity->user_id;
		}
		else if (isset($entity->User))
		{
			$user = $entity->User;
			if ($user instanceof User)
			{
				return $user->user_id;
			}
			else
			{
				throw new \LogicException("Found a User relation but it did not match a user; please override");
			}
		}

		throw new \LogicException("Could not determine content user ID; please override");
	}

	public function getEntityWith()
	{
		return [];
	}

	public function getContent($id)
	{
		return \XF::app()->findByContentType($this->contentType, $id, $this->getEntityWith());
	}

	public function getCountsFieldName()
	{
		return $this->contentCacheFields['counts'] ?? false;
	}

	public function getRecentFieldName()
	{
		return $this->contentCacheFields['recent'] ?? false;
	}

	public function getContentType()
	{
		return $this->contentType;
	}

	public static function getWebhookEvents(): array
	{
		return ['react', 'unreact'];
	}
}
