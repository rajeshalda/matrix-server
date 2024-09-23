<?php

namespace XF\Admin\Controller;

use XF\AdminNavigation;
use XF\Finder\EmailBounceLogFinder;
use XF\Finder\ModeratorLogFinder;
use XF\Finder\PaymentProviderLogFinder;
use XF\Finder\SpamCleanerLogFinder;
use XF\Finder\SpamTriggerLogFinder;
use XF\Install\Helper;
use XF\Repository\AddOnRepository;
use XF\Repository\ErrorLogRepository;
use XF\Repository\FileCheckRepository;
use XF\Repository\SessionActivityRepository;
use XF\Repository\StatsRepository;
use XF\Repository\TemplateRepository;
use XF\Repository\UpgradeCheckRepository;
use XF\Service\Stats\GrapherService;
use XF\Stats\Grouper\AbstractGrouper;
use XF\Util\Php;

class IndexController extends AbstractController
{
	public function actionIndex()
	{
		/** @var Template $templateRepo */
		$templateRepo = $this->repository(TemplateRepository::class);

		$showUnicodeWarning = (
			\XF::db()->getSchemaManager()->hasUnicodeMismatch($mismatchType)
			&& $mismatchType == 'loose'
		);

		// TODO: put these bits and pieces into configurable / selectable widgets
		/** @var AdminNavigation $nav */
		$nav = $this->app['navigation.admin'];

		/** @var FileCheckRepository $fileCheckRepo */
		$fileCheckRepo = $this->repository(FileCheckRepository::class);

		/** @var SessionActivityRepository $activityRepo */
		$activityRepo = $this->repository(SessionActivityRepository::class);

		$stats = [];
		if (\XF::visitor()->hasAdminPermission('viewStatistics'))
		{
			/** @var AbstractGrouper $grouper */
			$grouper = $this->app->create('stats.grouper', 'daily');

			foreach ($this->getDashboardStatGraphs() AS $statDisplayTypes)
			{
				$now = \XF::$time;
				$start = $now - 30 * 86400;
				$end = $now - ($now % 86400) - 1; // yesterday
				/** @var GrapherService $grapher */
				$grapher = $this->service(GrapherService::class, $start, $end, $statDisplayTypes);
				$stats[] = [
					'data' => $grapher->getGroupedData($grouper),
					'phrases' => $this->repository(StatsRepository::class)->getStatsTypePhrases($statDisplayTypes),
				];
			}
		}

		$logCounts = [];
		if (\XF::visitor()->hasAdminPermission('viewLogs'))
		{
			$cutOffs = [
				'day' => \XF::$time - 86400,
				'week' => \XF::$time - 86400 * 7,
				'month' => \XF::$time - 86400 * 30,
			];
			foreach ($this->getLogSummaryTypes() AS $logKey => $logData)
			{
				$values = [];
				foreach ($cutOffs AS $cutOffType => $cutOffDate)
				{
					$finder = $this->finder($logData['finder'])
						->where($logData['date'], '>=', $cutOffDate);

					if (!empty($logData['where']))
					{
						$finder->where($logData['where']);
					}

					$values[$cutOffType] = $finder->total();
				}

				$logCounts[$logKey] = $values;
			}
		}

		$installed = [];

		foreach ($this->app->addOnManager()->getInstalledAddOns() AS $id => $addOn)
		{
			if ($id == 'XF' || $addOn->canUpgrade() || $addOn->isLegacy())
			{
				continue;
			}

			if ($addOn->isInstalled())
			{
				$installed[$id] = $addOn;
			}
		}

		$installHelper = new Helper($this->app);
		$requirementErrors = $installHelper->getRequirementErrors();

		/** @var UpgradeCheckRepository $upgradeCheckRepo */
		$upgradeCheckRepo = $this->repository(UpgradeCheckRepository::class);
		$upgradeCheck = $upgradeCheckRepo->canCheckForUpgrades() ? $upgradeCheckRepo->getLatestUpgradeCheck() : null;

		$viewParams = [
			'outdatedTemplates' => $templateRepo->countOutdatedTemplates(),
			'showUnicodeWarning' => $showUnicodeWarning,
			'hasStoppedJobs' => $this->app->jobManager()->hasStoppedJobs(),
			'hasStoppedManualJobs' => $this->app->jobManager()->hasStoppedManualJobs(),
			'serverErrorLogs' => $this->repository(ErrorLogRepository::class)->hasErrorsInLog(),
			'legacyConfig' => file_exists($this->app->container('config.legacyFile')),
			'fileChecks' => $fileCheckRepo->findFileChecksForList()->fetch(5),
			'navigation' => $nav->getTree(),
			'installedAddOns' => $installed,
			'hasProcessingAddOn' => $this->repository(AddOnRepository::class)->hasAddOnsBeingProcessed(),
			'staffOnline' => $activityRepo->getOnlineStaffList(),
			'stats' => $stats,
			'logCounts' => $logCounts,
			'envReport' => Php::getEnvironmentReport(),
			'requirementErrors' => $requirementErrors,
			'upgradeCheck' => $upgradeCheck,
			'isImportRunning' => $this->app->import()->manager()->isImportRunning(),
		];
		return $this->view('XF:Index', 'index', $viewParams);
	}

	protected function getDashboardStatGraphs()
	{
		return [
			['post', 'thread'],
			['user_registration', 'user_activity'],
		];
	}

	protected function getLogSummaryTypes()
	{
		return [
			'moderator' => [
				'finder' => ModeratorLogFinder::class,
				'date' => 'log_date',
			],
			'spamTrigger' => [
				'finder' => SpamTriggerLogFinder::class,
				'date' => 'log_date',
			],
			'spamCleaner' => [
				'finder' => SpamCleanerLogFinder::class,
				'date' => 'application_date',
			],
			'emailBounce' => [
				'finder' => EmailBounceLogFinder::class,
				'date' => 'log_date',
			],
			'payment' => [
				'finder' => PaymentProviderLogFinder::class,
				'date' => 'log_date',
				'where' => [
					['log_type', '=', 'payment'],
				],
			],
		];
	}
}
