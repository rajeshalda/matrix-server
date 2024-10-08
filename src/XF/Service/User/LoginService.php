<?php

namespace XF\Service\User;

use XF\App;
use XF\Behavior\ChangeLoggable;
use XF\Db\Exception;
use XF\Entity\User;
use XF\Entity\UserAuth;
use XF\Repository\LoginAttemptRepository;
use XF\Service\AbstractService;
use XF\Validator\Email;

use function strlen;

class LoginService extends AbstractService
{
	protected $login;
	protected $ip;

	protected $recordAttempts = true;
	protected $allowPasswordUpgrade = true;

	public function __construct(App $app, $login, $ip)
	{
		parent::__construct($app);

		$this->login = $login;
		$this->ip = $ip;
	}

	public function setRecordAttempts($value)
	{
		$this->recordAttempts = (bool) $value;
	}

	public function getRecordAttempts()
	{
		return $this->recordAttempts;
	}

	public function setAllowPasswordUpgrade($value)
	{
		$this->allowPasswordUpgrade = (bool) $value;
	}

	public function getAllowPasswordUpgrade()
	{
		return $this->allowPasswordUpgrade;
	}

	public function isLoginLimited(&$limitType = null)
	{
		if (!strlen($this->login) || !$this->ip)
		{
			return false;
		}

		if ($this->hasTooManyLoginAttempts($this->ip))
		{
			$limitType = $this->app->options()->loginLimit;
			return true;
		}

		return false;
	}

	public function hasTooManyLoginAttempts($ip)
	{
		if (!$ip)
		{
			return false;
		}

		$limits = $this->getAttemptLimits();

		/** @var LoginAttemptRepository $attemptRepo */
		$attemptRepo = $this->repository(LoginAttemptRepository::class);

		foreach ($limits AS $limit)
		{
			$login = ($limit['type'] == 'user' ? $this->login : null);
			$cutOff = \XF::$time - $limit['time'];
			$count = $limit['count'];

			if ($attemptRepo->countLoginAttemptsSince($cutOff, $ip, $login) >= $count)
			{
				return true;
			}
		}

		return false;
	}

	public function getAttemptLimits()
	{
		return [
			['type' => 'user', 'time' => 60 * 5, 'count' => 4],
			['type' => 'user', 'time' => 60 * 30, 'count' => 8],
			['type' => 'ip',   'time' => 60 * 5, 'count' => 8],
			['type' => 'ip',   'time' => 60 * 30, 'count' => 16],
		];
	}

	public function validate($password, &$error = null)
	{
		if (!strlen($this->login))
		{
			$error = \XF::phrase('requested_user_not_found');
			return null;
		}

		$user = $this->getUser();
		if (!$user)
		{
			$this->recordFailedAttempt();

			$error = \XF::phrase('requested_user_x_not_found', ['name' => $this->login]);
			return null;
		}

		if (!strlen($password))
		{
			// don't log an attempt if they don't provide a password

			$error = \XF::phrase('incorrect_password');
			return null;
		}

		$auth = $user->Auth;
		if (!$auth || !$auth->authenticate($password))
		{
			$this->recordFailedAttempt();

			$error = \XF::phrase('incorrect_password');
			return null;
		}

		if ($this->allowPasswordUpgrade)
		{
			/** @var UserAuth $userAuth */
			$userAuth = $user->Auth;
			if ($userAuth->getAuthenticationHandler()->isUpgradable())
			{
				$userAuth->getBehavior(ChangeLoggable::class)->setOption('enabled', false);
				$userAuth->setPassword($password, null, false); // don't update the password date as this isn't a real change
				$userAuth->save();
			}
		}

		$this->clearFailedAttempts();

		return $user;
	}

	/**
	 * @return null|User
	 */
	protected function getUser()
	{
		$emailValidator = $this->app->validator(Email::class);
		$email = $emailValidator->coerceValue($this->login);
		if ($emailValidator->isValid($email))
		{
			$user = $this->findOne(User::class, ['email' => $email], ['Auth']);
			if ($user)
			{
				return $user;
			}
		}

		return $this->findOne(User::class, ['username' => $this->login], ['Auth']);
	}

	protected function recordFailedAttempt()
	{
		if (!$this->ip || !$this->recordAttempts)
		{
			return;
		}

		try
		{
			/** @var LoginAttemptRepository $attemptRepo */
			$attemptRepo = $this->repository(LoginAttemptRepository::class);
			$attemptRepo->logFailedLogin($this->login, $this->ip);
		}
		catch (Exception $e)
		{

		}
	}

	protected function clearFailedAttempts()
	{
		if (!$this->ip || !$this->recordAttempts)
		{
			return;
		}

		try
		{
			/** @var LoginAttemptRepository $attemptRepo */
			$attemptRepo = $this->repository(LoginAttemptRepository::class);
			$attemptRepo->clearLoginAttempts($this->login, $this->ip);
		}
		catch (Exception $e)
		{
		}
		// this can interfere with logging in, so don't suppress
	}
}
