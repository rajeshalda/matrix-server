<?php

namespace XF\Job;

use GuzzleHttp\Utils;
use XF\Repository\CollectStatsRepository;
use XF\Repository\OptionRepository;

class CollectStats extends AbstractJob
{
	public function run($maxRunTime)
	{
		$this->performStatsCollection();

		// jitter between 0 and 4 days on top of the base 10 days. This should ensure some randomness
		// of the requests to the XF server so not all sites try to communicate at the same time
		// while still ensuring that we collect stats once every ~2 weeks.
		$continueDate = \XF::$time + 10 * 24 * 3600;
		$offsetJitter = mt_rand(0, 4 * 24 * 3600);
		$continueDate += $offsetJitter;

		$result = $this->resume();
		$result->continueDate = $continueDate;

		return $result;
	}

	protected function performStatsCollection()
	{
		/** @var CollectStatsRepository $collectStatsRepo */
		$collectStatsRepo = $this->app->repository(CollectStatsRepository::class);

		if (!$collectStatsRepo->isEnabled())
		{
			return;
		}

		$success = true;
		$error = null;

		$stats = $collectStatsRepo->collectStats();
		if ($stats)
		{
			$client = $this->app->http()->client();

			try
			{
				$response = $client->post(\XF::XF_API_URL . 'submit-stats.json', [
					'http_errors' => false,
					'form_params' => [
						'stats' => $stats,
					],
				]);

				$responseJson = Utils::jsonDecode($response->getBody()->getContents(), true);

				if ($response->getStatusCode() !== 200)
				{
					$success = false;
					if (isset($responseJson['error']))
					{
						$error = reset($responseJson['error']);
					}
					else
					{
						$error = 'An unexpected error occurred.';
					}
				}
			}
			catch (\Exception $e)
			{
				$success = false;
				$error = $e->getMessage();
			}
		}

		if (!$success)
		{
			\XF::logError('XenForo stats collection failed: ' . $error);
		}

		$serverStatsConfig = $this->app->options()->collectServerStats;
		$serverStatsConfig['last_sent'] = time();

		/** @var OptionRepository $optionRepo */
		$optionRepo = $this->app->repository(OptionRepository::class);

		// skip verifying the option here as only last_sent will have changed
		$optionRepo->updateOptionSkipVerify('collectServerStats', $serverStatsConfig);
	}

	public function getStatusMessage()
	{
		return \XF::phrase('collecting_server_stats');
	}

	public function canCancel()
	{
		return false;
	}

	public function canTriggerByChoice()
	{
		return false;
	}
}
