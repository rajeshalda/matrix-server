<?php

namespace XF\Warning;

use XF\Entity\Post;
use XF\Entity\Warning;
use XF\Mvc\Entity\Entity;
use XF\Service\Post\DeleterService;

use function is_string, strlen;

class PostHandler extends AbstractHandler
{
	public function getStoredTitle(Entity $entity)
	{
		return $entity->Thread ? $entity->Thread->title : '';
	}

	public function getDisplayTitle($title)
	{
		return \XF::phrase('post_in_thread_x', ['title' => $title]);
	}

	public function getContentForConversation(Entity $entity)
	{
		return $entity->message;
	}

	public function getContentUser(Entity $entity)
	{
		/** @var Post $entity */
		return $entity->User;
	}

	public function canViewContent(Entity $entity, &$error = null)
	{
		/** @var Post $entity */
		return $entity->canView();
	}

	public function onWarning(Entity $entity, Warning $warning)
	{
		$entity->warning_id = $warning->warning_id;
		$entity->save();
	}

	public function onWarningRemoval(Entity $entity, Warning $warning)
	{
		$entity->warning_id = 0;
		$entity->warning_message = '';
		$entity->save();
	}

	public function takeContentAction(Entity $entity, $action, array $options)
	{
		if ($action == 'public')
		{
			$message = $options['message'] ?? '';
			if (is_string($message) && strlen($message))
			{
				$entity->warning_message = $message;
				$entity->save();
			}
		}
		else if ($action == 'delete')
		{
			$reason = $options['reason'] ?? '';
			if (!is_string($reason))
			{
				$reason = '';
			}

			/** @var DeleterService $deleter */
			$deleter = \XF::app()->service(DeleterService::class, $entity);
			$deleter->delete('soft', $reason);
		}
	}

	protected function canWarnPublicly(Entity $entity)
	{
		return true;
	}

	protected function canDeleteContent(Entity $entity)
	{
		/** @var Post $entity */
		return $entity->canDelete('soft');
	}

	public function getEntityWith()
	{
		$visitor = \XF::visitor();
		return ['Thread', 'Thread.Forum', 'Thread.Forum.Node.Permissions|' . $visitor->permission_combination_id];
	}
}
