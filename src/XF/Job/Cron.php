<?php

namespace XF\Job;

use XF\Finder\CronEntryFinder;
use XF\Service\CronEntry\CalculateNextRunService;

use function call_user_func;

class Cron extends AbstractJob
{
	public function run($maxRunTime)
	{
		$start = microtime(true);

		/** @var CalculateNextRunService $cronService */
		$cronService = $this->app->service(CalculateNextRunService::class);

		$entries = $this->app->finder(CronEntryFinder::class)
			->whereAddOnActive()
			->where('active', 1)
			->where('next_run', '<=', \XF::$time)
			->order('next_run');

		foreach ($entries->fetch() AS $entry)
		{
			$hasCallback = $entry->hasCallback();

			if (!$cronService->updateCronRunTimeAtomic($entry))
			{
				continue;
			}

			try
			{
				if ($hasCallback)
				{
					$entry['cron_class'] = $this->app->extendClass($entry['cron_class']);

					call_user_func(
						[$entry['cron_class'], $entry['cron_method']],
						$entry
					);
				}
			}
			catch (\Exception $e)
			{
				// suppress so we don't get stuck -- make sure we rollback though as don't know the state
				$this->app->logException($e, true);
			}

			if (microtime(true) - $start >= $maxRunTime)
			{
				break;
			}
		}

		$result = $this->resume();
		$result->continueDate = $cronService->getMinimumNextRunTime();
		return $result;
	}

	public function getStatusMessage()
	{
		$actionPhrase = \XF::phrase('running');
		$typePhrase = \XF::phrase('cron_entries');
		return sprintf('%s... %s', $actionPhrase, $typePhrase);
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
