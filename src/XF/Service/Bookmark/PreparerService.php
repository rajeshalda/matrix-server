<?php

namespace XF\Service\Bookmark;

use XF\App;
use XF\Entity\BookmarkItem;
use XF\Service\AbstractService;

class PreparerService extends AbstractService
{
	/**
	 * @var BookmarkItem
	 */
	protected $bookmark;

	public function __construct(App $app, BookmarkItem $bookmark)
	{
		parent::__construct($app);
		$this->setBookmarkItem($bookmark);
	}

	protected function setBookmarkItem(BookmarkItem $bookmark)
	{
		$this->bookmark = $bookmark;
	}

	public function getBookmarkItem()
	{
		return $this->bookmark;
	}

	public function setMessage($message, $format = true)
	{
		$preparer = $this->getStructuredTextPreparer($format);
		$preparer->setConstraint('maxLength', $this->bookmark->getMaxLength('message'));
		$this->bookmark->message = $preparer->prepare($message);

		return $preparer->pushEntityErrorIfInvalid($this->bookmark);
	}

	/**
	 * @param bool $format
	 *
	 * @return \XF\Service\StructuredText\PreparerService
	 */
	protected function getStructuredTextPreparer($format = true)
	{
		/** @var \XF\Service\StructuredText\PreparerService $preparer */
		$preparer = $this->service(\XF\Service\StructuredText\PreparerService::class, 'bookmark', $this->bookmark);
		if (!$format)
		{
			$preparer->disableAllFilters();
		}

		return $preparer;
	}

	public function afterInsert()
	{
	}

	public function afterUpdate()
	{
	}
}
