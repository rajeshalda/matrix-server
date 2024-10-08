<?php

namespace XF\Repository;

use XF\ActivitySummary\Instance;
use XF\Finder\ActivitySummaryDefinitionFinder;
use XF\Finder\ActivitySummarySectionFinder;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class ActivitySummaryRepository extends Repository
{
	/**
	 * @return Finder
	 */
	public function findActivitySummarySectionsForList(): ActivitySummarySectionFinder
	{
		return $this->finder(ActivitySummarySectionFinder::class)
			->definitionActive()
			->setDefaultOrder('display_order');
	}

	public function findActivitySummarySectionsForDisplay(): ActivitySummarySectionFinder
	{
		return $this->finder(ActivitySummarySectionFinder::class)
			->activeOnly()
			->setDefaultOrder('display_order');
	}

	/**
	 * @return Finder
	 */
	public function findActivitySummaryDefinitionsForList($activeOnly = false)
	{
		$finder = $this->finder(ActivitySummaryDefinitionFinder::class)->order('definition_id');
		if ($activeOnly)
		{
			$finder->with('AddOn')
				->whereAddOnActive();
		}
		return $finder;
	}

	public function getActivitySummaryRecipientIds()
	{
		$limit = $this->options()->activitySummaryEmailBatchLimit;

		$emailFreqCutOff = $this->getEmailFrequencyCutOff();
		[$minCutOff, $maxCutOff] = $this->getLastActivityCutOff();

		return $this->db()->fetchAllColumn("
			SELECT user_id
			FROM xf_user
			WHERE last_summary_email_date < ?
			    AND last_summary_email_date > 0
				AND last_activity < ?
				AND last_activity > ?
				AND user_state = 'valid'
			    AND is_banned = 0
				AND email <> ''
			ORDER BY last_summary_email_date
			LIMIT ?
		", [$emailFreqCutOff, $minCutOff, $maxCutOff, $limit]);
	}

	public function getEmailFrequencyCutOff()
	{
		$activitySummaryEmail = $this->options()->activitySummaryEmail;
		return \XF::$time - $activitySummaryEmail['email_frequency_days'] * 86400;
	}

	public function getLastActivityCutOff()
	{
		$activitySummaryEmail = $this->options()->activitySummaryEmail;

		$minCutOff = \XF::$time - $activitySummaryEmail['last_activity_min_days'] * 86400;

		$maxCutOff = 0;
		if ($activitySummaryEmail['last_activity_max_days'])
		{
			$maxCutOff = \XF::$time - $activitySummaryEmail['last_activity_max_days'] * 86400;
		}

		return [$minCutOff, $maxCutOff];
	}

	public function getMinLastActivityCutOff()
	{
		[$minCutOff, $maxCutOff] = $this->getLastActivityCutOff();

		return $minCutOff;
	}

	public function getMaxLastActivityCutOff()
	{
		[$minCutOff, $maxCutOff] = $this->getLastActivityCutOff();

		return $maxCutOff;
	}

	public function addInstanceSpecificDisplayValues(Instance $instance)
	{
		$user = $instance->getUser();

		if ($user->alerts_unviewed)
		{
			// count unviewed alerts if last activity was over 30 days ago (the alert expiry cut off)
			if ($user->getValue('last_activity') < \XF::$time - (30 * 86400))
			{
				/** @var UserAlertRepository $alertRepo */
				$alertRepo = $this->repository(UserAlertRepository::class);
				$alertRepo->updateUnviewedCountForUser($user);
			}

			if ($user->alerts_unviewed)
			{
				$instance->addDisplayValue(\XF::phrase('alerts'), $user->alerts_unviewed);
			}
		}

		if ($user->conversations_unread)
		{
			$instance->addDisplayValue(\XF::phrase('direct_messages'), $user->conversations_unread);
		}

		/** @var ReactionRepository $reactionRepo */
		$reactionRepo = $this->repository(ReactionRepository::class);

		$reactionScore = $reactionRepo->getUserReactionScoreSince($user, $this->getMinLastActivityCutOff());
		if ($reactionScore)
		{
			$instance->addDisplayValue(\XF::phrase('reaction_score'), $reactionScore);
		}

		$this->app()->fire('activity_summary_instance_display_values', [&$instance]);
	}

	public function getGlobalDisplayValues()
	{
		$globalDisplayValues = [];

		/** @var UserRepository $userRepo */
		$userRepo = $this->repository(UserRepository::class);

		$userCount = $userRepo->findValidUsers()
			->where('register_date', '>', $this->getMinLastActivityCutOff())
			->total();

		if ($userCount)
		{
			$globalDisplayValues[] = [
				'label' => \XF::phrase('members'),
				'value' => $userCount,
			];
		}

		$this->app()->fire('activity_summary_global_display_values', [&$globalDisplayValues]);

		return $globalDisplayValues;
	}
}
