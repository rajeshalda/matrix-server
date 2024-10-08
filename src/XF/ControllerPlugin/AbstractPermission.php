<?php

namespace XF\ControllerPlugin;

use XF\Entity\User;
use XF\Entity\UserGroup;
use XF\Finder\UserFinder;
use XF\Mvc\Entity\Entity;
use XF\Mvc\ParameterBag;
use XF\Repository\PermissionEntryRepository;
use XF\Repository\PermissionRepository;
use XF\Repository\UserGroupRepository;
use XF\Service\UpdatePermissionsService;

use function is_string;

abstract class AbstractPermission extends AbstractPlugin
{
	protected $viewFormatter = '';
	protected $templateFormatter = '';
	protected $routePrefix = '';
	protected $contentType = '';
	protected $entityIdentifier = '';
	protected $primaryKey = '';
	protected $privatePermissionGroupId = '';
	protected $privatePermissionId = '';

	public function setFormatters($view, $template)
	{
		$this->viewFormatter = $view;
		$this->templateFormatter = $template;
	}

	public function setRoutePrefix($prefix)
	{
		$this->routePrefix = $prefix;
	}

	public function setPrimaryKey($key)
	{
		$this->primaryKey = $key;
	}

	public function setContentType($contentType)
	{
		$this->contentType = $contentType;
	}

	public function setEntityIdentifier($identifier)
	{
		$this->entityIdentifier = $identifier;
	}

	public function setPrivatePermissionIds($groupId, $permissionId)
	{
		$this->privatePermissionGroupId = $groupId;
		$this->privatePermissionId = $permissionId;
	}

	public function actionList(ParameterBag $params)
	{
		$record = $this->assertRecordExists($this->entityIdentifier, $params->{$this->primaryKey});

		/** @var PermissionEntryRepository $entryRepo */
		$entryRepo = $this->repository(PermissionEntryRepository::class);
		$entries = $entryRepo->getContentPermissionEntriesGrouped($this->contentType, $record->{$this->primaryKey});

		if ($entries['users'])
		{
			$users = $this->finder(UserFinder::class)
				->whereIds(array_keys($entries['users']))
				->order('username')
				->fetch();
		}
		else
		{
			$users = [];
		}

		/** @var UserGroupRepository $userGroupRepo */
		$userGroupRepo = $this->repository(UserGroupRepository::class);
		$userGroups = $userGroupRepo->findUserGroupsForList()->fetch();

		$isPrivate = (
			isset($entries['system'][$this->privatePermissionGroupId][$this->privatePermissionId])
			&& $entries['system'][$this->privatePermissionGroupId][$this->privatePermissionId] == 'reset'
		);

		$viewParams = [
			'record' => $record,
			'entries' => $entries,
			'userGroups' => $userGroups,
			'users' => $users,
			'isPrivate' => $isPrivate,
			'routePrefix' => $this->routePrefix,
		];
		return $this->view(
			$this->formatView('List'),
			$this->formatTemplate('list'),
			$viewParams
		);
	}

	public function actionEdit(ParameterBag $params, $type = null)
	{
		$record = $this->assertRecordExists($this->entityIdentifier, $params->{$this->primaryKey});

		if ($type === null)
		{
			$type = $this->filter('type', 'str');
			if (!$type)
			{
				if ($this->filter('user_group_id', 'uint'))
				{
					$type = 'user_group';
				}
				else if ($this->filter('user_id', 'uint'))
				{
					$type = 'user';
				}
			}
		}

		/** @var PermissionRepository $permissionRepo */
		$permissionRepo = $this->repository(PermissionRepository::class);
		$permissionData = $permissionRepo->getContentPermissionListData($this->contentType);

		/** @var PermissionEntryRepository $entryRepo */
		$entryRepo = $this->repository(PermissionEntryRepository::class);
		$entries = $entryRepo->getContentPermissionEntriesGrouped($this->contentType, $record->{$this->primaryKey});

		if ($type == 'user_group')
		{
			$userGroup = $this->assertRecordExists(UserGroup::class, $this->filter('user_group_id', 'uint'));
			$user = null;

			$typeEntries = $entries['groups'][$userGroup->user_group_id] ?? [];

			$saveParams = [
				'type' => 'user_group',
				'user_group_id' => $userGroup->user_group_id,
			];
		}
		else if ($type == 'user')
		{
			$username = $this->filter('username', 'str');
			if ($username)
			{
				$user = $this->em()->findOne(User::class, ['username' => $username]);
				if (!$user)
				{
					return $this->error(\XF::phrase('requested_user_not_found'), 404);
				}

				return $this->redirect($this->buildLink(
					$this->routePrefix . '/edit',
					$record,
					['user_id' => $user->user_id]
				));
			}

			$userId = $this->filter('user_id', 'uint');

			if (!$userId)
			{
				return $this->error(\XF::phrase('requested_user_not_found'), 404);
			}

			$user = $this->assertRecordExists(User::class, $userId);
			$userGroup = null;

			$typeEntries = $entries['users'][$user->user_id] ?? [];

			$saveParams = [
				'type' => 'user',
				'user_id' => $user->user_id,
			];
		}
		else
		{
			return $this->notFound();
		}

		$viewParams = [
			'record' => $record,
			'userGroup' => $userGroup,
			'user' => $user,
			'permissionData' => $permissionData,
			'typeEntries' => $typeEntries,
			'saveParams' => $saveParams,
			'routePrefix' => $this->routePrefix,
		];
		return $this->view(
			$this->formatView('Edit'),
			$this->formatTemplate('edit'),
			$viewParams
		);
	}

	public function actionSave(ParameterBag $params, $type = null)
	{
		$this->assertPostOnly();

		$record = $this->assertRecordExists($this->entityIdentifier, $params->{$this->primaryKey});

		if ($type === null)
		{
			$type = $this->filter('type', 'str');
		}

		if ($type == 'private')
		{
			$this->savePrivate($record);
		}
		else if ($type == 'user_group')
		{
			$this->saveUserGroup($record);
		}
		else if ($type == 'user')
		{
			$this->saveUser($record);
		}

		return $this->redirect($this->buildLink($this->routePrefix, $record));
	}

	protected function savePrivate(Entity $record)
	{
		$makePrivate = $this->filter('private', 'bool');
		$update = [
			$this->privatePermissionGroupId => [
				$this->privatePermissionId => $makePrivate ? 'reset' : 'unset',
			],
		];

		/** @var UpdatePermissionsService $permissionUpdater */
		$permissionUpdater = $this->service(UpdatePermissionsService::class);
		$permissionUpdater->setContent($this->contentType, $record->{$this->primaryKey})->updatePermissions($update);
	}

	protected function saveUserGroup(Entity $record)
	{
		$userGroup = $this->assertRecordExists(UserGroup::class, $this->filter('user_group_id', 'uint'));
		$permissions = $this->filter('permissions', 'array');

		/** @var UpdatePermissionsService $permissionUpdater */
		$permissionUpdater = $this->service(UpdatePermissionsService::class);
		$permissionUpdater->setContent($this->contentType, $record->{$this->primaryKey})->setUserGroup($userGroup);
		$permissionUpdater->updatePermissions($permissions);
	}

	protected function saveUser(Entity $record)
	{
		$user = $this->assertRecordExists(User::class, $this->filter('user_id', 'uint'));
		$permissions = $this->filter('permissions', 'array');

		/** @var UpdatePermissionsService $permissionUpdater */
		$permissionUpdater = $this->service(UpdatePermissionsService::class);
		$permissionUpdater->setContent($this->contentType, $record->{$this->primaryKey})->setUser($user);
		$permissionUpdater->updatePermissions($permissions);
	}

	protected function formatView($type)
	{
		$formatter = $this->viewFormatter;

		if (is_string($formatter))
		{
			return sprintf($formatter, $type);
		}
		else if ($formatter instanceof \Closure)
		{
			return $formatter($type);
		}
		else
		{
			return '';
		}
	}

	protected function formatTemplate($type)
	{
		$formatter = $this->templateFormatter;

		if (is_string($formatter))
		{
			return sprintf($formatter, $type);
		}
		else if ($formatter instanceof \Closure)
		{
			return $formatter($type);
		}
		else
		{
			return '';
		}
	}
}
