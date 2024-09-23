<?php

namespace XF\ApprovalQueue;

use XF\Entity\ProfilePostComment;
use XF\Mvc\Entity\Entity;
use XF\Service\ProfilePostComment\ApproverService;

class ProfilePostCommentHandler extends AbstractHandler
{
	protected function canActionContent(Entity $content, &$error = null)
	{
		/** @var $content \XF\Entity\ProfilePostComment */
		return $content->canApproveUnapprove($error);
	}

	public function actionApprove(ProfilePostComment $comment)
	{
		/** @var ApproverService $approver */
		$approver = \XF::service(ApproverService::class, $comment);
		$approver->approve();
	}

	public function actionDelete(ProfilePostComment $comment)
	{
		$this->quickUpdate($comment, 'message_state', 'deleted');
	}

	public function actionSpamClean(ProfilePostComment $comment)
	{
		if (!$comment->User)
		{
			return;
		}

		$this->_spamCleanInternal($comment->User);
	}
}
