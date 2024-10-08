<?php

namespace XF\AdminSearch;

use XF\Entity\Option;
use XF\Finder\OptionFinder;
use XF\Finder\OptionGroupFinder;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Router;

class OptionHandler extends AbstractHandler
{
	public function getDisplayOrder()
	{
		return 10;
	}

	public function search($text, $limit, array $previousMatchIds = [])
	{
		$optFinder = $this->app->finder(OptionFinder::class);

		$conditions = [
			[$optFinder->columnUtf8('option_id'), 'like', $optFinder->escapeLike($text, '%?%')],
		];
		if ($previousMatchIds)
		{
			$conditions[] = ['option_id', $previousMatchIds];
		}

		$optFinder
			->whereOr($conditions)
			->order($optFinder->caseInsensitive('option_id'))
			->limit($limit);

		$results = $optFinder->fetch();

		// TODO: option_group, option_group_description phrases?

		$groupFinder = $this->app->finder(OptionGroupFinder::class);

		if (!\XF::$debugMode)
		{
			$groupFinder->where('debug_only', 0);
		}

		$groups = $groupFinder->fetch();

		return $results->filter(function (Option $option) use ($groups)
		{
			$exists = false;

			foreach ($option->Relations AS $groupId => $relation)
			{
				if (isset($groups[$groupId]))
				{
					$exists = true;
					break;
				}
			}

			return $exists;
		});
	}

	public function getRelatedPhraseGroups()
	{
		return ['option', 'option_explain'];
	}

	public function getTemplateData(Entity $record)
	{
		/** @var Router $router */
		$router = $this->app->container('router.admin');

		return [
			'link' => $router->buildLink('options/view', $record),
			'title' => $record->title,
			'extra' => $record->option_id,
		];
	}

	public function isSearchable()
	{
		return \XF::visitor()->hasAdminPermission('option');
	}
}
