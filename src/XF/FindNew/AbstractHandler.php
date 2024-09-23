<?php

namespace XF\FindNew;

use XF\Entity\FindNew;
use XF\Http\Request;
use XF\Mvc\Controller;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\ArrayCollection;

abstract class AbstractHandler
{
	protected $contentType;

	abstract public function getRoute();
	abstract public function getPageReply(
		Controller $controller,
		FindNew $findNew,
		array $results,
		$page,
		$perPage
	);
	abstract public function getFiltersFromInput(Request $request);
	abstract public function getDefaultFilters();
	abstract public function getResultIds(array $filters, $maxResults);
	abstract public function getPageResultsEntities(array $ids);
	abstract public function getResultsPerPage();

	public function __construct($contentType)
	{
		$this->contentType = $contentType;
	}

	public function isAvailable()
	{
		return true;
	}

	/**
	 * @param array $ids
	 *
	 * @return ArrayCollection
	 */
	public function getPageResults(array $ids)
	{
		$results = $this->getPageResultsEntities($ids);
		$results = $this->filterResults($results);
		return $results->sortByList($ids);
	}

	protected function filterResults(AbstractCollection $results)
	{
		return $results->filterViewable();
	}

	public function getContentType()
	{
		return $this->contentType;
	}
}
