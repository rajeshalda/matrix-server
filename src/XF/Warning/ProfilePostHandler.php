<?php

namespace XF\Warning;

use XF\Entity\ProfilePost;
use XF\Entity\Warning;
use XF\Mvc\Entity\Entity;
use XF\Service\ProfilePost\DeleterService;

use function is_string, strlen;

class ProfilePostHandler extends AbstractHandler
{
	public function getStoredTitle(Entity $entity)
	{
		return $entity->User ? $entity->User->username : '';
	}

	public function getDisplayTitle($title)
	{
		return \XF::phrase('profile_post_by_x', ['name' => $title]);
	}

	public function getContentForConversation(Entity $entity)
	{
		return $entity->message;
	}

	public function getContentUrl(Entity $entity, $canonical = false)
	{
		return \XF::app()->router('public')->buildLink(($canonical ? 'canonical:' : '') . 'profile-posts', $entity);
	}

	public function getContentUser(Entity $entity)
	{
		/** @var ProfilePost $entity */
		return $entity->User;
	}

	public function canViewContent(Entity $entity, &$error = null)
	{
		/** @var ProfilePost $entity */
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
		/** @var ProfilePost $entity */
		return $entity->canDelete('soft');
	}

	public function getEntityWith()
	{
		return ['ProfileUser', 'ProfileUser.Privacy'];
	}
}
