<?php

namespace XF\ApprovalQueue;

use XF\Entity\ProfilePost;
use XF\Mvc\Entity\Entity;
use XF\Service\ProfilePost\ApproverService;

class ProfilePostHandler extends AbstractHandler
{
	protected function canActionContent(Entity $content, &$error = null)
	{
		/** @var $content \XF\Entity\ProfilePost */
		return $content->canApproveUnapprove($error);
	}

	public function actionApprove(ProfilePost $profilePost)
	{
		/** @var ApproverService $approver */
		$approver = \XF::service(ApproverService::class, $profilePost);
		$approver->approve();
	}

	public function actionDelete(ProfilePost $profilePost)
	{
		$this->quickUpdate($profilePost, 'message_state', 'deleted');
	}

	public function actionSpamClean(ProfilePost $profilePost)
	{
		if (!$profilePost->User)
		{
			return;
		}

		$this->_spamCleanInternal($profilePost->User);
	}

	public function getEntityWith()
	{
		return ['ProfileUser', 'ProfileUser.Privacy'];
	}
}
