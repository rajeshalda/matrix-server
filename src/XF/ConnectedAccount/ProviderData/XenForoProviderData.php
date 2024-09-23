<?php

namespace XF\ConnectedAccount\ProviderData;

use XF\Entity\ConnectedAccountProvider;

class XenForoProviderData extends AbstractProviderData
{
	public function getDefaultEndpoint(): string
	{
		$provider = \XF::app()->em()->find(
			ConnectedAccountProvider::class,
			$this->providerId
		);

		return $provider->options['board_url'] . '/api/me';
	}

	public function getProviderKey()
	{
		$data = $this->requestFromEndpoint();
		return $data['me']['user_id'];
	}

	public function getUsername()
	{
		$data = $this->requestFromEndpoint();
		return $data['me']['username'];
	}

	public function getEmail()
	{
		$data = $this->requestFromEndpoint();
		return $data['me']['email'] ?? '';
	}

	public function getProfileLink()
	{
		$data = $this->requestFromEndpoint();
		return $data['me']['view_url'];
	}

	public function getLocation()
	{
		$data = $this->requestFromEndpoint();
		return $data['me']['location'];
	}

	public function getAvatarUrl()
	{
		$data = $this->requestFromEndpoint();
		return $data['me']['avatar_urls']['o'];
	}
}
