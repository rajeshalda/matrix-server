<?php

namespace XF\Sitemap;

use XF\Entity\Node;
use XF\Finder\NodeFinder;

class NodeHandler extends AbstractHandler
{
	public function getRecords($start)
	{
		$app = $this->app;
		$user = \XF::visitor();

		$nodeIds = $this->getIds('xf_node', 'node_id', $start);

		$nodeFinder = $app->finder(NodeFinder::class);
		$nodes = $nodeFinder
			->where('node_id', $nodeIds)
			->with(['Permissions|' . $user->permission_combination_id])
			->order('node_id')
			->fetch();

		return $nodes;
	}

	public function getEntry($record)
	{
		/** @var Node $record */
		$url = $this->app->router('public')->buildLink('canonical:' . $record->getRoute(), $record);
		return Entry::create($url);
	}

	public function isIncluded($record)
	{
		/** @var Node $record */
		if (!$record->isSearchEngineIndexable())
		{
			return false;
		}

		return $record->canView();
	}
}
