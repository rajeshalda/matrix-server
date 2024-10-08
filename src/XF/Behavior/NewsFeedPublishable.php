<?php

namespace XF\Behavior;

use XF\Entity\User;
use XF\Mvc\Entity\Behavior;
use XF\Repository\NewsFeedRepository;

class NewsFeedPublishable extends Behavior
{
	protected function getDefaultConfig()
	{
		return [
			'userIdField' => 'user_id',
			'usernameField' => null,
			'dateField' => null,
		];
	}

	protected function getDefaultOptions()
	{
		return [
			'enabled' => true,
		];
	}

	protected function verifyConfig()
	{
		if (!$this->contentType())
		{
			throw new \LogicException("Structure must provide a contentType value");
		}
	}

	public function postSave()
	{
		if ($this->entity->isInsert() && $this->options['enabled'])
		{
			$this->publish();
		}
	}

	public function publish()
	{
		if (!$this->isPublishable())
		{
			return;
		}

		/** @var NewsFeedRepository $newsFeedRepo */
		$newsFeedRepo = $this->repository(NewsFeedRepository::class);

		$userIdField = $this->config['userIdField'];
		$usernameField = $this->config['usernameField'];
		$dateField = $this->config['dateField'];
		$entity = $this->entity;

		if ($userIdField instanceof \Closure)
		{
			$userId = $userIdField($entity);
		}
		else
		{
			$userId = $entity->getValue($userIdField);
		}

		$username = null;

		if ($userId)
		{
			$user = $this->app()->em()->find(User::class, $userId);
			if ($user)
			{
				$username = $user->username;
			}
		}

		if ($username === null)
		{
			if ($usernameField instanceof \Closure)
			{
				$username = $usernameField($entity);
			}
			else if ($usernameField)
			{
				$username = $entity->getValue($usernameField);
			}
		}

		if ($username === null)
		{
			$username = '';
		}

		if ($dateField)
		{
			if ($dateField instanceof \Closure)
			{
				$date = $dateField($entity);
			}
			else
			{
				$date = $entity->getValue($dateField);
			}
		}
		else
		{
			$date = null;
		}

		$newsFeedRepo->publish(
			$this->contentType(),
			$this->id(),
			'insert',
			$userId,
			$username,
			[],
			$date
		);
	}

	protected function isPublishable()
	{
		$handler = $this->repository(NewsFeedRepository::class)->getNewsFeedHandler($this->contentType(), true);
		if (!$handler->isPublishable($this->entity, 'insert'))
		{
			return false;
		}

		return true;
	}

	public function postDelete()
	{
		/** @var NewsFeedRepository $newsFeedRepo */
		$newsFeedRepo = $this->repository(NewsFeedRepository::class);
		$newsFeedRepo->unpublish($this->contentType(), $this->id());
	}
}
