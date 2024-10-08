<?php

namespace XF\Criteria;

use XF\App;
use XF\Data\TimeZone;
use XF\Entity\User;
use XF\Repository\ConnectedAccountRepository;
use XF\Repository\LanguageRepository;
use XF\Repository\NodeRepository;
use XF\Repository\StyleRepository;
use XF\Repository\UserGroupRepository;
use XF\Util\Arr;
use XF\Util\Php;
use XF\Util\Str;

use function is_array;

abstract class AbstractCriteria
{
	/**
	 * @var App
	 */
	protected $app;

	/**
	 * @var array
	 */
	protected $criteria = [];

	/**
	 * @var bool
	 */
	protected $matchOnEmpty = true;

	public function __construct(App $app, array $criteria)
	{
		$this->app = $app;
		$this->setCriteria($criteria);
	}

	public function isMatched(User $user)
	{
		if (!$this->criteria)
		{
			return $this->matchOnEmpty;
		}

		foreach ($this->criteria AS $criterion)
		{
			$rule = $criterion['rule'];
			$data = $criterion['data'];

			$specialResult = $this->isSpecialMatched($rule, $data, $user);
			if ($specialResult === false)
			{
				return false;
			}
			else if ($specialResult === true)
			{
				continue;
			}

			$method = '_match' . Php::camelCase($rule);
			if (method_exists($this, $method))
			{
				$result = $this->$method($data, $user);
				if (!$result)
				{
					return false;
				}
			}
			else
			{
				if (!$this->isUnknownMatched($rule, $data, $user))
				{
					return false;
				}
			}
		}

		return true;
	}

	protected function isSpecialMatched($rule, array $data, User $user)
	{
		return null;
	}

	protected function isUnknownMatched($rule, array $data, User $user)
	{
		return false;
	}

	public function setCriteria(array $criteria)
	{
		$this->criteria = $this->filterCriteria($criteria);
	}

	public function getCriteria()
	{
		return $this->criteria;
	}

	public function getCriteriaForTemplate()
	{
		$output = [];
		foreach ($this->criteria AS $criterion)
		{
			$data = (!empty($criterion['data']) ? $criterion['data'] : true);
			$output[$criterion['rule']] = $data;
		}

		return $output;
	}

	public function setMatchOnEmpty($matchOnEmpty)
	{
		$this->matchOnEmpty = (bool) $matchOnEmpty;
	}

	public function getMatchOnEmpty()
	{
		return $this->matchOnEmpty;
	}

	public function getExtraTemplateData()
	{
		/** @var ConnectedAccountRepository $connectedAccRepo */
		$connectedAccRepo = $this->app->repository(ConnectedAccountRepository::class);
		$connectedAccProviders = $connectedAccRepo->getConnectedAccountProviderTitlePairs();

		/** @var UserGroupRepository $userGroupRepo */
		$userGroupRepo = $this->app->repository(UserGroupRepository::class);
		$userGroups = $userGroupRepo->getUserGroupTitlePairs();

		/** @var LanguageRepository $languageRepo */
		$languageRepo = $this->app->repository(LanguageRepository::class);
		$languageTree = $languageRepo->getLanguageTree(false);

		$hours = [];
		for ($i = 0; $i < 24; $i++)
		{
			$hh = str_pad($i, 2, '0', STR_PAD_LEFT);
			$hours[$hh] = $hh;
		}

		$minutes = [];
		for ($i = 0; $i < 60; $i += 5)
		{
			$mm = str_pad($i, 2, '0', STR_PAD_LEFT);
			$minutes[$mm] = $mm;
		}

		/** @var TimeZone $tzData */
		$tzData = $this->app->data(TimeZone::class);
		$timeZones = $tzData->getTimeZoneOptions();

		/** @var NodeRepository $nodeRepo */
		$nodeRepo = $this->app->repository(NodeRepository::class);
		$nodes = $nodeRepo->getNodeOptionsData(false);

		$styleRepo = $this->app->repository(StyleRepository::class);
		$styleTree = $styleRepo->getStyleTree(false);

		$templateData = [
			'connectedAccProviders' => $connectedAccProviders,
			'userGroups' => $userGroups,
			'languageTree' => $languageTree,

			'hours' => $hours,
			'minutes' => $minutes,
			'timeZones' => $timeZones,
			'nodes' => $nodes,
			'styleTree' => $styleTree,
		];

		$this->app->fire('criteria_template_data', [&$templateData]);

		return $templateData;
	}

	protected function filterCriteria(array $criteria)
	{
		$criteriaFiltered = [];
		foreach ($criteria AS $criterion)
		{
			if (!empty($criterion['rule']))
			{
				if (empty($criterion['data']) || !is_array($criterion['data']))
				{
					$criterion['data'] = [];
				}
				$criteriaFiltered[] = [
					'rule' => $criterion['rule'],
					'data' => $criterion['data'],
				];
			}
		}

		return $criteriaFiltered;
	}

	protected function findNeedle($needleList, $haystack)
	{
		$haystack = Str::strtolower($haystack);

		foreach (Arr::stringToArray(Str::strtolower($needleList), '/\s*,\s*/') AS $needle)
		{
			if (strpos($haystack, $needle) !== false)
			{
				return $needle;
			}
		}

		return false;
	}
}
