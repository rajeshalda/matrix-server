<?php

namespace XF\Service\User;

use XF\Behavior\ChangeLoggable;
use XF\Entity\UserAuth;
use XF\Repository\IpRepository;
use XF\Repository\UserRememberRepository;

class PasswordResetService extends AbstractConfirmationService
{
	protected $isAdminReset = false;
	protected $allowPasswordReuse = true;

	public function getType()
	{
		return 'password';
	}

	public function setAdminReset($isAdminReset)
	{
		$this->isAdminReset = (bool) $isAdminReset;
	}

	public function setAllowPasswordReuse($allow)
	{
		$this->allowPasswordReuse = $allow;
	}

	protected function getEmailTemplateParams()
	{
		$params = parent::getEmailTemplateParams();
		$params['isAdminReset'] = $this->isAdminReset;

		return $params;
	}

	public function canTriggerConfirmation(&$error = null)
	{
		if ($timeLimit = $this->app->options()->lostPasswordTimeLimit)
		{
			if ($this->confirmation->exists())
			{
				$timeDiff = time() - $this->confirmation->confirmation_date;
				if ($timeLimit > $timeDiff)
				{
					$wait = $timeLimit - $timeDiff;
					$error = \XF::phrase('must_wait_x_seconds_before_performing_this_action', ['count' => $wait]);
					return false;
				}
			}
		}

		if ($this->user->email == '')
		{
			$error = \XF::phrase('this_account_cannot_be_confirmed_without_email_address');
			return false;
		}

		return true;
	}

	protected function getRecordLifetime(): int
	{
		return 12 * 3600; // 12 hours
	}

	public function resetLostPassword($newPassword)
	{
		$user = $this->user;

		/** @var UserAuth $userAuth */
		$userAuth = $user->getRelationOrDefault('Auth', false);
		if (!$this->isAdminReset)
		{
			$userAuth->getBehavior(ChangeLoggable::class)->setOption('forceEditUserId', $user->user_id);
		}
		$userAuth->setPassword($newPassword, null, true, $this->allowPasswordReuse);
		$userAuth->save();

		if ($this->confirmation->exists())
		{
			$this->confirmation->delete();
		}

		$this->repository(UserRememberRepository::class)->clearUserRememberRecords($user->user_id);

		$ip = $this->app->request()->getIp();
		$this->repository(IpRepository::class)->logIp(
			$user->user_id,
			$ip,
			'user',
			$user->user_id,
			'reset_password'
		);

		if ($user->email)
		{
			$this->app->mailer()->newMail()
				->setToUser($user)
				->setTemplate('user_lost_password_reset', ['user' => $user])
				->send();
		}

		return $user;
	}
}
