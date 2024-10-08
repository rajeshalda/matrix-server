<?php

namespace XF\Admin\Controller;

use XF\ControllerPlugin\DeletePlugin;
use XF\ControllerPlugin\TogglePlugin;
use XF\Entity\MemberStat;
use XF\Finder\MemberStatFinder;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;
use XF\Repository\MemberStatRepository;
use XF\Repository\PermissionRepository;
use XF\Searcher\User;

class MemberStatController extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertAdminPermission('user');
	}

	public function actionIndex(ParameterBag $params)
	{
		$memberStatRepo = $this->getMemberStatRepo();
		$memberStats = $memberStatRepo->findMemberStatsForList()->fetch();

		$viewParams = [
			'memberStats' => $memberStats,
		];
		return $this->view('XF:MemberStat\Listing', 'member_stat_list', $viewParams);
	}

	protected function memberStatAddEdit(MemberStat $memberStat)
	{
		$searcher = $this->searcher(User::class);
		$searcher->setCriteria($memberStat->criteria ?: []);

		$permissionRepo = $this->repository(PermissionRepository::class);
		$permissionsData = $permissionRepo->getGlobalPermissionListData();

		$viewParams = [
			'memberStat' => $memberStat,
			'criteria' => $searcher->getFormCriteria(),
			'sortOrders' => $searcher->getOrderOptions(),
			'permissionsData' => $permissionsData,
		];
		return $this->view('XF:MemberStat\Edit', 'member_stat_edit', $viewParams + $searcher->getFormData());
	}

	public function actionEdit(ParameterBag $params)
	{
		$notice = $this->assertMemberStatExists($params->member_stat_id);
		return $this->memberStatAddEdit($notice);
	}

	public function actionAdd()
	{
		/** @var MemberStat $memberStat */
		$memberStat = $this->em()->create(MemberStat::class);
		return $this->memberStatAddEdit($memberStat);
	}

	protected function memberStatSaveProcess(MemberStat $memberStat)
	{
		$form = $this->formAction();

		$entityInput = $this->filter([
			'member_stat_key' => 'str',
			'criteria' => 'array',
			'sort_order' => 'str',
			'sort_direction' => 'str',
			'callback_class' => 'str',
			'callback_method' => 'str',
			'visibility_class' => 'str',
			'visibility_method' => 'str',
			'permission_limit' => 'str',
			'show_value' => 'bool',
			'overview_display' => 'bool',
			'active' => 'bool',
			'user_limit' => 'uint',
			'display_order' => 'uint',
			'addon_id' => 'str',
			'cache_lifetime' => 'uint',
		]);

		$form->basicEntitySave($memberStat, $entityInput);


		$extraInput = $this->filter([
			'title' => 'str',
		]);
		$form->validate(function (FormAction $form) use ($extraInput)
		{
			if ($extraInput['title'] === '')
			{
				$form->logError(\XF::phrase('please_enter_valid_title'), 'title');
			}
		});
		$form->apply(function () use ($extraInput, $memberStat)
		{
			$title = $memberStat->getMasterPhrase();
			$title->phrase_text = $extraInput['title'];
			$title->save();
		});

		return $form;
	}

	public function actionSave(ParameterBag $params)
	{
		$this->assertPostOnly();

		if ($params->member_stat_id)
		{
			$memberStat = $this->assertMemberStatExists($params->member_stat_id);
		}
		else
		{
			$memberStat = $this->em()->create(MemberStat::class);
		}

		$this->memberStatSaveProcess($memberStat)->run();

		return $this->redirect($this->buildLink('member-stats') . $this->buildLinkHash($memberStat->member_stat_id));
	}

	public function actionDelete(ParameterBag $params)
	{
		$memberStat = $this->assertMemberStatExists($params->member_stat_id);
		if (!$memberStat->canEdit())
		{
			return $this->error(\XF::phrase('item_cannot_be_deleted_associated_with_addon_explain'));
		}

		/** @var DeletePlugin $plugin */
		$plugin = $this->plugin(DeletePlugin::class);
		return $plugin->actionDelete(
			$memberStat,
			$this->buildLink('member-stats/delete', $memberStat),
			$this->buildLink('member-stats/edit', $memberStat),
			$this->buildLink('member-stats'),
			$memberStat->title
		);
	}

	public function actionToggle()
	{
		/** @var TogglePlugin $plugin */
		$plugin = $this->plugin(TogglePlugin::class);
		return $plugin->actionToggle(MemberStatFinder::class);
	}

	/**
	 * @param string $id
	 * @param array|string|null $with
	 * @param null|string $phraseKey
	 *
	 * @return MemberStat
	 */
	protected function assertMemberStatExists($id, $with = null, $phraseKey = null)
	{
		return $this->assertRecordExists(MemberStat::class, $id, $with, $phraseKey);
	}

	/**
	 * @return MemberStatRepository
	 */
	protected function getMemberStatRepo()
	{
		return $this->repository(MemberStatRepository::class);
	}
}
