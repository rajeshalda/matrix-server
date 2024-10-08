<?php

namespace XF\Tfa;

use XF\Entity\User;
use XF\Http\Request;

use function ord;

class Email extends AbstractProvider
{
	public function generateInitialData(User $user, array $config = [])
	{
		return [];
	}

	public function trigger($context, User $user, array &$config, Request $request)
	{
		$length = 6;

		$random = \XF::generateRandomString(4, true);
		$code = (
			((ord($random[0]) & 0x7f) << 24) |
			((ord($random[1]) & 0xff) << 16) |
			((ord($random[2]) & 0xff) << 8) |
			(ord($random[3]) & 0xff)
		);
		$code = $code % 10 ** $length;
		$code = str_pad($code, $length, '0', STR_PAD_LEFT);

		$config['code'] = $code;
		$config['codeGenerated'] = time();

		$ip = $request->getIp();

		\XF::mailer()->newMail()
			->setToUser($user)
			->setTemplate('two_step_login_email', [
				'user' => $user,
				'ip' => $ip,
				'code' => $code,
			])
			->send();

		return [];
	}

	public function render($context, User $user, array $config, array $triggerData)
	{
		$params = [
			'config' => $config,
			'context' => $context,
		];
		return \XF::app()->templater()->renderTemplate('public:two_step_email', $params);
	}

	public function verify($context, User $user, array &$config, Request $request)
	{
		if (empty($config['code']) || empty($config['codeGenerated']))
		{
			return false;
		}

		if (time() - $config['codeGenerated'] > 900)
		{
			return false;
		}

		$code = $request->filter('code', 'str');
		$code = preg_replace('/[^0-9]/', '', $code);

		if (!hash_equals($config['code'], $code))
		{
			return false;
		}

		unset($config['code']);
		unset($config['codeGenerated']);

		return true;
	}

	public function meetsRequirements(User $user, &$error)
	{
		if (!$user->email || $user->user_state != 'valid')
		{
			$error = \XF::phrase('you_must_have_valid_email_account_confirmed');
			return false;
		}

		return true;
	}
}
