<?php

namespace XF\Service\MemberStat;

use XF\App;
use XF\Behavior\DevOutputWritable;
use XF\Entity\MemberStat;
use XF\Mvc\Entity\Finder;
use XF\Searcher\User;
use XF\Service\AbstractService;
use XF\Util\Php;

use function call_user_func_array, is_array;

class PreparerService extends AbstractService
{
	/**
	 * @var MemberStat
	 */
	protected $memberStat;

	/**
	 * @var User
	 */
	protected $userSearcher;

	protected $validOnly = true;

	public function __construct(App $app, MemberStat $memberStat)
	{
		parent::__construct($app);
		$this->setMemberStat($memberStat);
		$this->setUserSearcher();
	}

	public function setMemberStat(MemberStat $memberStat)
	{
		$this->memberStat = $memberStat;
	}

	/**
	 * @return MemberStat
	 */
	public function getMemberStat()
	{
		return $this->memberStat;
	}

	public function setUserSearcher()
	{
		$this->userSearcher = $this->app->searcher(User::class);
	}

	/**
	 * @return User
	 */
	public function getUserSearcher()
	{
		return $this->userSearcher;
	}

	public function setValidOnly($validOnly)
	{
		$this->validOnly = $validOnly;
	}

	public function cache()
	{
		$memberStat = $this->memberStat;
		$results = $this->getResultsData();
		if (!is_array($results))
		{
			// this indicates an error happened with the searcher, so don't cache the results
			return [];
		}

		$memberStat->cache_expiry = \XF::$time + ($memberStat->cache_lifetime * 60);
		$memberStat->cache_results = $results;

		$memberStat->getBehavior(DevOutputWritable::class)->setOption('write_dev_output', false);

		$memberStat->save();

		return $results;
	}

	public function getResults($forOverview = false)
	{
		$memberStat = $this->memberStat;

		if ($forOverview && !$memberStat->overview_display)
		{
			return [];
		}

		$results = $memberStat->cache_results;

		if (!is_array($results))
		{
			if ($memberStat->cache_lifetime > 0)
			{
				// the cache is null, but the stat is cacheable so cache it
				$results = $this->cache();
			}
			else
			{
				// no cache defined so just get the data
				$results = $this->getResultsData() ?: [];
			}
		}
		else
		{
			// allow cache to expire + 50% before re-caching
			$gracePeriod = $memberStat->cache_lifetime * 60 / 2;
			if (\XF::$time > $memberStat->cache_expiry + $gracePeriod && !$forOverview)
			{
				$results = $this->cache();
			}
		}

		$valueField = $this->getValueField();
		if ($valueField)
		{
			$results = $this->prepareCacheResults($valueField, $results);
		}

		return $results;
	}

	public function setCriteria($criteria)
	{
		if (!$criteria)
		{
			return;
		}
		$this->userSearcher->setCriteria($criteria);
	}

	public function applyCallback($class, $method, Finder $finder, $additionalParams = [])
	{
		if (!$class || !$method)
		{
			return null;
		}

		$memberStat = $this->memberStat;

		$class = $this->app->extendClass($class);
		if (Php::validateCallbackPhrased($class, $method))
		{
			$results = call_user_func_array([$class, $method], [$memberStat, $finder] + $additionalParams);
			return $results;
		}
		else
		{
			return null;
		}
	}

	public function setOrder($order, $direction)
	{
		if (!$order || !$direction)
		{
			$order = 'message_count';
			$direction = 'desc';
		}
		$this->userSearcher->setOrder($order, $direction);
	}

	protected function prepareCacheResults($order, array $cacheResults)
	{
		switch ($order)
		{
			case 'reaction_score':
			case 'message_count':
			case 'trophy_points':
				$cacheResults = array_map(function ($value)
				{
					return \XF::language()->numberFormat($value);
				}, $cacheResults);
				break;

			case 'register_date':
			case 'last_activity':
				$cacheResults = array_map(function ($value)
				{
					return \XF::language()->dateTime($value);
				}, $cacheResults);
				break;
		}

		$this->app->fire('member_stat_result_prepare', [$order, &$cacheResults]);

		return $cacheResults;
	}

	/**
	 * @return array|null
	 */
	protected function getResultsData()
	{
		$memberStat = $this->memberStat;
		$searcher = $this->userSearcher;

		$this->setCriteria($memberStat->criteria);
		$this->setOrder($memberStat->sort_order, $memberStat->sort_direction);

		$finder = $searcher->getFinder();
		if ($this->validOnly)
		{
			$finder->isValidUser();
		}

		$results = $this->applyCallback($memberStat->callback_class, $memberStat->callback_method, $finder);

		if (!is_array($results)) // callback may have fetched the data already, if not, we'll do it here
		{
			$valueField = $this->getValueField();
			if ($valueField)
			{
				if (!$finder->isColumnValid($valueField))
				{
					return null;
				}

				$finder->whereOr(
					[$valueField, '>', 0],
					[$valueField, '<', 0]
				);
			}

			if ($memberStat->user_limit > 0)
			{
				$limit = $memberStat->user_limit * 3;
			}
			else
			{
				// technically means unlimited, but doing this accidentally could cause serious problems
				$limit = 200;
			}

			$results = $finder->fetch($limit);

			if ($valueField)
			{
				$results = $results->pluckNamed($valueField, 'user_id');
				if (!is_array($results))
				{
					$results = [];
				}
			}
			else
			{
				$results = $results->pluck(function (\XF\Entity\User $user)
				{
					return [$user->user_id, null];
				}, false);
			}
		}

		return $results;
	}

	/**
	 * @return string|null
	 */
	protected function getValueField()
	{
		if (
			!$this->memberStat->show_value ||
			!$this->userSearcher->isSortOrderNumeric($this->memberStat->sort_order)
		)
		{
			return null;
		}

		return $this->memberStat->sort_order;
	}
}
