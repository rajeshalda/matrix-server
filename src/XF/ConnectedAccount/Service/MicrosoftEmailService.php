<?php

namespace XF\ConnectedAccount\Service;

use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\OAuth2\Service\AbstractService;
use OAuth\OAuth2\Token\StdOAuth2Token;

use function is_array;

class MicrosoftEmailService extends AbstractService
{
	/**
	 * IMAP
	 */
	public const SCOPE_IMAP = 'https://outlook.office.com/IMAP.AccessAsUser.All';

	/**
	 * POP
	 */
	public const SCOPE_POP = 'https://outlook.office.com/POP.AccessAsUser.All';

	/**
	 * SMTP AUTH
	 */
	public const SCOPE_SMTP_AUTH = 'https://outlook.office.com/SMTP.Send';

	/**
	 * openid
	 */
	public const SCOPE_OPENID = 'openid';

	/**
	 * profile
	 */
	public const SCOPE_PROFILE = 'profile';

	/**
	 * email
	 */
	public const SCOPE_EMAIL = 'email';

	/**
	 * offline_access
	 */
	public const SCOPE_OFFLINE_ACCESS = 'offline_access';

	protected function parseAccessTokenResponse($responseBody)
	{
		$data = json_decode($responseBody, true);

		if (!is_array($data))
		{
			throw new TokenResponseException('Unable to parse response.');
		}
		else if (isset($data['error']))
		{
			throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
		}

		$token = new StdOAuth2Token();
		$token->setAccessToken($data['access_token']);
		$token->setLifetime($data['expires_in']);

		if (isset($data['refresh_token']))
		{
			$token->setRefreshToken($data['refresh_token']);
			unset($data['refresh_token']);
		}

		unset($data['access_token']);
		unset($data['expires_in']);

		$token->setExtraParams($data);

		return $token;
	}

	public function getAuthorizationEndpoint()
	{
		return new Uri('https://login.microsoftonline.com/organizations/oauth2/v2.0/authorize');
	}

	public function getAccessTokenEndpoint()
	{
		return new Uri('https://login.microsoftonline.com/organizations/oauth2/v2.0/token');
	}

	protected function getAuthorizationMethod()
	{
		return static::AUTHORIZATION_METHOD_QUERY_STRING;
	}
}
