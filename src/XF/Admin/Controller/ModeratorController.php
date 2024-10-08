<?php

namespace XF\Admin\Controller;

use XF\ControllerPlugin\DeletePlugin;
use XF\Entity\Moderator;
use XF\Entity\ModeratorContent;
use XF\Entity\User;
use XF\Finder\ModeratorContentFinder;
use XF\Finder\UserFinder;
use XF\Mvc\ParameterBag;
use XF\Repository\ModeratorRepository;
use XF\Repository\PermissionEntryRepository;
use XF\Repository\UserGroupRepository;
use XF\Service\UpdatePermissionsService;
use XF\Util\Arr;

class ModeratorController extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertAdminPermission('user');
	}

	public function actionIndex(ParameterBag $params)
	{
		$modRepo = $this->getModRepo();

		$superModerators = $modRepo->findModeratorsForList(true)->fetch();

		$contentModFinder = $modRepo->findContentModeratorsForList();

		$userIdFilter = $this->filter('user_id', 'uint');
		if ($userIdFilter)
		{
			$moderator = $this->assertGeneralModeratorExists($userIdFilter);
			$displayLimit = null;

			$contentModFinder->where('user_id', $moderator->user_id);
		}
		else
		{
			$moderator = null;
			$displayLimit =  10;
		}

		$contentModerators = $contentModFinder
			->where('content_type', array_keys($modRepo->getModeratorHandlers()))
			->fetch();

		$groupedModerators = $modRepo->getGroupedContentModeratorsForList(
			$contentModerators,
			$displayLimit
		);
		$contentModeratorTotals = $modRepo->getContentModeratorTotals();

		$users = $this->finder(UserFinder::class)
			->whereIds(array_keys($contentModeratorTotals))
			->order('username')
			->fetch();

		$viewParams = [
			'superModerators' => $superModerators,
			'contentModerators' => $groupedModerators,
			'contentModeratorTotals' => $contentModeratorTotals,
			'displayLimit' => $displayLimit,
			'users' => $users,
			'userIdFilter' => $userIdFilter,
		];
		return $this->view('XF:Moderator\Listing', 'moderator_list', $viewParams);
	}

	protected function moderatorAddEdit(
		Moderator $generalModerator,
		?ModeratorContent $contentModerator = null
	)
	{
		/** @var PermissionEntryRepository $permissionEntryRepo */
		$permissionEntryRepo = $this->repository(PermissionEntryRepository::class);

		$modRepo = $this->getModRepo();

		$existingPermissionValues = $permissionEntryRepo->getGlobalUserPermissionEntries($generalModerator->user_id);

		if ($contentModerator)
		{
			$moderatorHandler = $modRepo->getModeratorHandler($contentModerator->content_type);
			if (!$moderatorHandler)
			{
				return $this->error(\XF::phrase('this_content_moderator_relates_to_unknown_content_type'));
			}

			$contentTitle = $moderatorHandler->getContentTitle($contentModerator->content_id);

			$contentPermissionValues = $permissionEntryRepo->getContentUserPermissionEntries(
				$contentModerator->content_type,
				$contentModerator->content_id,
				$contentModerator->user_id
			);
			$existingPermissionValues = Arr::mapMerge($existingPermissionValues, $contentPermissionValues);
		}
		else
		{
			$contentTitle = '';
		}

		$user = $generalModerator->User;

		$moderatorPermissionData = $modRepo->getModeratorPermissionData(
			$contentModerator ? $contentModerator->content_type : null
		);

		$viewParams = [
			'user' => $user,
			'generalModerator' => $generalModerator,
			'contentModerator' => $contentModerator,

			'contentTitle' => $contentTitle,
			'isStaff' => $generalModerator->exists() ? $user->is_staff : true,

			'existingValues' => $existingPermissionValues,
			'allowValues' => ['allow', 'content_allow'],

			'interfaceGroups' => $moderatorPermissionData['interfaceGroups'],
			'globalPermissions' => $moderatorPermissionData['globalPermissions'],
			'contentPermissions' => $moderatorPermissionData['contentPermissions'],

			'userGroups' => $this->em()->getRepository(UserGroupRepository::class)->getUserGroupTitlePairs(),
		];

		return $this->view('XF:Moderator\Edit', 'moderator_edit', $viewParams);
	}

	public function actionContentEdit(ParameterBag $params)
	{
		$contentModerator = $this->assertContentModeratorExists($params['moderator_id']);
		$generalModerator = $this->assertGeneralModeratorExists($contentModerator->user_id);
		return $this->moderatorAddEdit($generalModerator, $contentModerator);
	}

	public function actionSuperEdit(ParameterBag $params)
	{
		$generalModerator = $this->assertGeneralModeratorExists($params['user_id']);
		return $this->moderatorAddEdit($generalModerator, null);
	}

	public function actionAdd()
	{
		$input = $this->filter([
			'username' => 'str',
			'type' => 'str',
			'type_id' => 'array-uint',
		]);

		if ($input['username'] === '' || $input['type'] === '')
		{
			$viewParams = [
				'typeHandlers' => $this->app->getContentTypeField('moderator_handler_class'),
				'type' => $input['type'],
				'typeId' => $input['type_id'],
			];
			return $this->view('XF:Moderator\AddChoice', 'moderator_add_choice', $viewParams);
		}

		$user = $this->finder(UserFinder::class)->where('username', $input['username'])->fetchOne();
		if (!$user)
		{
			return $this->error(\XF::phrase('requested_user_not_found'));
		}

		$generalModerator = $this->em()->find(Moderator::class, $user->user_id);
		if (!$generalModerator)
		{
			$generalModerator = $this->em()->create(Moderator::class);
			$generalModerator->user_id = $user->user_id;
			$generalModerator->is_super_moderator = ($input['type'] == '_super');
		}

		if ($input['type'] != '_super')
		{
			$handler = $this->getModRepo()->getModeratorHandler($input['type']);
			if (!$handler)
			{
				return $this->error(\XF::phrase('please_choose_valid_moderator_type'), 404);
			}

			$contentId = $input['type_id'][$input['type']] ?? 0;
			if (!$handler->getContentTitle($contentId))
			{
				return $this->error(\XF::phrase('please_select_a_valid_type_of_moderator'), 404);
			}

			$contentModerator = $this->finder(ModeratorContentFinder::class)
				->where([
					'content_type' => $input['type'],
					'content_id' => $contentId,
					'user_id' => $user->user_id,
				])
				->fetchOne();

			if (!$contentModerator)
			{
				$contentModerator = $this->em()->create(ModeratorContent::class);

				$contentModerator->content_type = $input['type'];
				$contentModerator->content_id = $contentId;
				$contentModerator->user_id = $user->user_id;
			}
		}
		else
		{
			$contentModerator = null;
		}

		return $this->moderatorAddEdit($generalModerator, $contentModerator);
	}

	protected function moderatorSaveProcess(
		Moderator $generalModerator,
		?ModeratorContent $contentModerator = null
	)
	{
		$form = $this->formAction();

		$input = $this->filter([
			'extra_user_group_ids' => 'array-uint',
			'globalPermissions' => 'array',
			'contentPermissions' => 'array',
			'is_staff' => 'bool',
		]);

		$user = $generalModerator->User;

		$form->basicEntitySave($user, [
			'is_staff' => $input['is_staff'],
		]);

		/** @var UpdatePermissionsService $permissionUpdater */
		$permissionUpdater = $this->service(UpdatePermissionsService::class);
		$permissionUpdater->setUser($user);

		$form->basicEntitySave($generalModerator, [
			'extra_user_group_ids' => $input['extra_user_group_ids'],
		]);
		$form->apply(function () use ($permissionUpdater, $input)
		{
			$permissionUpdater->setGlobal();
			$permissionUpdater->updatePermissions($input['globalPermissions']);
		});

		if ($contentModerator)
		{
			// need to get this saved, even though it has been configured already
			$form->basicEntitySave($contentModerator, []);

			$form->complete(function () use ($permissionUpdater, $contentModerator, $input)
			{
				$permissionUpdater->setContent($contentModerator->content_type, $contentModerator->content_id);
				$permissionUpdater->updatePermissions($input['contentPermissions']);
			});
		}

		// TODO: the permissions are actually rebuilt twice with this method

		return $form;
	}

	public function actionSave(ParameterBag $params)
	{
		$this->assertPostOnly();

		$findInput = $this->filter([
			'user_id' => 'uint',
			'content_type' => 'str',
			'content_id' => 'uint',
		]);

		$user = $this->assertRecordExists(User::class, $findInput['user_id']);

		$generalModerator = $this->em()->find(Moderator::class, $user->user_id);
		if (!$generalModerator)
		{
			$generalModerator = $this->em()->create(Moderator::class);
			$generalModerator->user_id = $user->user_id;
		}

		$contentModerator = null;
		if ($findInput['content_type'] && $findInput['content_id'])
		{
			$contentModerator = $this->finder(ModeratorContentFinder::class)->where($findInput)->fetchOne();
			if (!$contentModerator)
			{
				$contentModerator = $this->em()->create(ModeratorContent::class);
				$contentModerator->bulkSet($findInput);
			}
		}

		if (!$contentModerator)
		{
			$generalModerator->is_super_moderator = true;
		}

		$this->moderatorSaveProcess($generalModerator, $contentModerator)->run();

		return $this->redirect($this->buildLink('moderators'));
	}

	public function actionSuperDelete(ParameterBag $params)
	{
		$generalModerator = $this->assertGeneralModeratorExists($params['user_id']);

		/** @var DeletePlugin $plugin */
		$plugin = $this->plugin(DeletePlugin::class);
		return $plugin->actionDelete(
			$generalModerator,
			$this->buildLink('moderators/super/delete', $generalModerator),
			$this->buildLink('moderators/super/edit', $generalModerator),
			$this->buildLink('moderators'),
			$generalModerator->User->username,
			'moderator_super_delete'
		);
	}

	public function actionContentDelete(ParameterBag $params)
	{
		$contentModerator = $this->assertContentModeratorExists($params['moderator_id']);
		$handler = $this->getModRepo()->getModeratorHandler($contentModerator->content_type);
		$contentTitle = $handler->getContentTitle($contentModerator->content_id);

		/** @var DeletePlugin $plugin */
		$plugin = $this->plugin(DeletePlugin::class);
		return $plugin->actionDelete(
			$contentModerator,
			$this->buildLink('moderators/content/delete', $contentModerator),
			$this->buildLink('moderators/content/edit', $contentModerator),
			$this->buildLink('moderators'),
			sprintf(
				"%s %s%s%s",
				$contentModerator->User->username,
				\XF::language()->parenthesis_open,
				$contentTitle,
				\XF::language()->parenthesis_close
			)
		);
	}

	/**
	 * @param string $id
	 * @param array|string|null $with
	 * @param null|string $phraseKey
	 *
	 * @return Moderator
	 */
	protected function assertGeneralModeratorExists($id, $with = null, $phraseKey = null)
	{
		return $this->assertRecordExists(Moderator::class, $id, $with, $phraseKey);
	}

	/**
	 * @param string $id
	 * @param array|string|null $with
	 * @param null|string $phraseKey
	 *
	 * @return ModeratorContent
	 */
	protected function assertContentModeratorExists($id, $with = null, $phraseKey = null)
	{
		return $this->assertRecordExists(ModeratorContent::class, $id, $with, $phraseKey);
	}

	/**
	 * @return ModeratorRepository
	 */
	protected function getModRepo()
	{
		return $this->repository(ModeratorRepository::class);
	}
}
