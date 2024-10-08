<?php

namespace XF\ApprovalQueue;

use XF\Entity\ApprovalQueue;
use XF\Entity\User;
use XF\Mvc\Entity\Entity;
use XF\Repository\ChangeLogRepository;
use XF\Service\User\RegistrationCompleteService;

class UserHandler extends AbstractHandler
{
	protected function canViewContent(Entity $content, &$error = null)
	{
		return true;
	}

	protected function canActionContent(Entity $content, &$error = null)
	{
		return \XF::visitor()->canApproveRejectUser();
	}

	public function getEntityWith()
	{
		return ['Profile', 'PreRegAction'];
	}

	public function getTemplateData(ApprovalQueue $unapprovedItem)
	{
		$templateData = parent::getTemplateData($unapprovedItem);

		/** @var User $user */
		$user = $unapprovedItem->Content;
		$templateData['user'] = $user;

		$preRegAction = $user->PreRegAction;
		if ($preRegAction)
		{
			$templateData['preRegAction'] = $preRegAction;
			$templateData['preRegHandler'] = $preRegAction->getHandler();
		}

		// If we suspect this user to be a spammer, let's also grab their recent change logs.
		// This will highlight spam-like changes to their account they made after being sent to the approval queue.
		if ($user->isPossibleSpammer())
		{
			$changeRepo = \XF::repository(ChangeLogRepository::class);
			$changeFinder = $changeRepo->findChangeLogsByContent('user', $user->user_id);

			// just get 5 most recent - ignore "protected" entries as these are set by the system
			$changes = $changeFinder->where('protected', 0)->fetch(5);
			$changeRepo->addDataToLogs($changes);

			$templateData['changesGrouped'] = $changeRepo->groupChangeLogs($changes);
		}

		return $templateData;
	}

	public function actionApprove(User $user)
	{
		$user->user_state = 'valid';
		$user->save();

		\XF::app()->logger()->logModeratorAction('user', $user, 'approved');

		$notify = $this->getInput('notify', $user->user_id);
		if ($notify && $user->email)
		{
			\XF::app()->mailer()->newMail()
				->setTemplate('user_account_approved', ['user' => $user])
				->setToUser($user)
				->send();
		}

		/** @var RegistrationCompleteService $regComplete */
		$regComplete = \XF::app()->service(RegistrationCompleteService::class, $user);
		$regComplete->triggerCompletionActions();
	}

	public function actionReject(User $user)
	{
		$notify = $this->getInput('notify', $user->user_id);
		$reason = $this->getInput('reason', $user->user_id);

		$user->rejectUser($reason, \XF::visitor());

		if ($notify && $user->email)
		{
			\XF::app()->mailer()->newMail()
				->setTemplate('user_account_rejected', ['user' => $user, 'reason' => $reason])
				->setToUser($user)
				->send();
		}

		\XF::app()->logger()->logModeratorAction('user', $user, 'rejected', ['reason' => $reason]);
	}

	public function actionSpamClean(User $user)
	{
		$this->_spamCleanInternal($user);
	}
}
