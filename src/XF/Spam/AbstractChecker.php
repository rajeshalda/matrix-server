<?php

namespace XF\Spam;

use XF\App;
use XF\Spam\Checker\AbstractProvider;
use XF\Util\Ip;

abstract class AbstractChecker
{
	protected $app;

	/** @var AbstractProvider[] */
	protected $providers = [];

	protected $decisions = [];
	protected $details = [];
	protected $params = [];

	public function __construct(App $app)
	{
		$this->app = $app;
	}

	public function addProvider(AbstractProvider $provider)
	{
		$this->providers[] = $provider;
	}

	public function getFinalDecision()
	{
		$priorities = [
			'allowed' => 1,
			'moderated' => 2,
			'denied' => 3,
		];

		$output = 'allowed';
		$priority = $priorities[$output];

		foreach ($this->decisions AS $decision)
		{
			if ($priorities[$decision] > $priority)
			{
				$output = $decision;
				$priority = $priorities[$decision];
			}
		}

		return $output;
	}

	public function logSpamTrigger($contentType, $contentId)
	{
		$decisions = array_reverse($this->decisions);
		$decisions = array_filter($decisions, function ($decision)
		{
			return ($decision != 'allowed');
		});
		$result = reset($decisions); // this is the most recent failure

		switch ($result)
		{
			case 'denied':
			case 'moderated':
				break;

			default:
				return false;
		}

		$request = $this->app()->request();

		$ipAddress = Ip::stringToBinary($request->getIp());
		$userId = \XF::visitor()->user_id;

		if (!$contentId)
		{
			$contentId = null;
		}

		if ($contentType == 'user')
		{
			$userId = $contentId ?: 0;
		}

		$requestState = $this->getSpamTriggerRequestState();

		$values = [
			'content_type' => $contentType,
			'content_id' => $contentId,
			'log_date' => time(),
			'user_id' => $userId,
			'ip_address' => $ipAddress,
			'result' => $result,
			'details' => json_encode($this->details),
			'request_state' => json_encode($requestState),
		];

		$onDupe = [];
		foreach (['log_date', 'user_id', 'ip_address', 'result', 'details', 'request_state'] AS $update)
		{
			$onDupe[] = "$update = VALUES($update)";
		}
		$onDupe = implode(', ', $onDupe);

		$db = $this->app()->db();
		$rows = $db->insert('xf_spam_trigger_log', $values, false, $onDupe);

		return $rows == 1 ? $db->lastInsertId() : true;
	}

	protected function getSpamTriggerRequestState(): array
	{
		$request = $this->app()->request();

		return [
			'url' => $request->getRequestUri(),
			'referrer' => $request->getReferrer() ?: '',
			'_GET' => $_GET,
			'_POST' => $request->filterForLog($_POST),
		];
	}

	public function logDecision($type, $decision)
	{
		$this->decisions[$type] = $decision;
	}

	public function logDetail($type, $phraseKey, array $data = [])
	{
		$detail = ['phrase' => $phraseKey];
		if ($data)
		{
			$detail['data'] = $data;
		}

		$this->details[$type] = $detail;
	}

	public function getDetails()
	{
		return $this->details;
	}

	public function logParam($key, $value)
	{
		$this->params[$key] = $value;
	}

	public function getDecision($key)
	{
		return $this->decisions[$key] ?? 'rejected';
	}

	/**
	 * @return App
	 */
	protected function app()
	{
		return $this->app;
	}
}
