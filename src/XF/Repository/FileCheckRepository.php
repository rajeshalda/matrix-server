<?php

namespace XF\Repository;

use XF\Entity\FileCheck;
use XF\Finder\FileCheckFinder;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class FileCheckRepository extends Repository
{
	/**
	 * @return Finder
	 */
	public function findFileChecksForList()
	{
		return $this->finder(FileCheckFinder::class)
			->setDefaultOrder('check_date', 'DESC');
	}

	public function pruneFileChecks($cutOff = null)
	{
		if ($cutOff === null)
		{
			$cutOff = \XF::$time - 86400 * 60;
		}

		/** @var FileCheck[] $fileChecks */
		$fileChecks = $this->finder(FileCheckFinder::class)
			->where('check_date', '<', $cutOff)
			->order('check_date', 'ASC')
			->fetch(1000);
		foreach ($fileChecks AS $fileCheck)
		{
			$fileCheck->delete();
		}
	}
}
