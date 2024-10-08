<?php

namespace XF\Service\Bookmark;

use XF\App;
use XF\Entity\BookmarkItem;
use XF\Entity\BookmarkLabelUse;
use XF\Entity\User;
use XF\Finder\BookmarkLabelUseFinder;
use XF\Repository\BookmarkRepository;
use XF\Service\AbstractService;

use function is_array;

class LabelChangerService extends AbstractService
{
	/**
	 * @var BookmarkItem
	 */
	protected $bookmark;

	/**
	 * @var User
	 */
	protected $user;

	protected $existingLabelUses = [];

	protected $createLabels = [];
	protected $addLabels = [];
	protected $removeLabels = [];

	public function __construct(App $app, BookmarkItem $bookmark, User $user)
	{
		parent::__construct($app);
		$this->bookmark = $bookmark;
		$this->user = $user;

		if ($bookmark->isUpdate())
		{
			$this->existingLabelUses = $this->finder(BookmarkLabelUseFinder::class)
				->where('bookmark_id', $bookmark->bookmark_id)
				->keyedBy('label_id')
				->fetch()->toArray();
		}
	}

	public function setLabels($labelList)
	{
		if (!is_array($labelList))
		{
			$labelList = $this->splitLabels($labelList);
		}

		$removeExisting = $this->existingLabelUses;

		$addLabels = $this->getBookmarkRepo()->getLabelsForUser($labelList, $this->user, $createLabels);
		foreach ($addLabels AS $label)
		{
			$id = $label->label_id;
			if (isset($this->existingLabelUses[$id]) && !isset($this->removeLabels[$id]))
			{
				// label already applied
				unset($removeExisting[$id]);
				continue;
			}

			$this->addLabels[$id] = $label->label;
		}

		$this->createLabels = array_combine($createLabels, $createLabels);

		foreach ($removeExisting AS $label)
		{
			/** @var BookmarkLabelUse $label */
			$this->removeLabels[$label->label_id] = $label->label;
		}
	}

	public function save()
	{
		$bookmarkRepo = $this->getBookmarkRepo();
		$bookmark = $this->bookmark;

		$this->db()->beginTransaction();

		foreach ($this->createLabels AS $create)
		{
			$label = $bookmarkRepo->createLabelForUser($create, $this->user);
			if ($label)
			{
				$this->addLabels[$label->label_id] = $label->label;
			}
		}

		$cache = $bookmarkRepo->modifyBookmarkLabelUses(
			$bookmark,
			array_keys($this->addLabels),
			array_keys($this->removeLabels)
		);

		$this->db()->commit();

		return $cache;
	}

	/**
	 * @param string $labels
	 *
	 * @return array
	 */
	protected function splitLabels($labels)
	{
		return $this->getBookmarkRepo()->splitLabels($labels);
	}

	/**
	 * @return BookmarkRepository
	 */
	protected function getBookmarkRepo()
	{
		return $this->repository(BookmarkRepository::class);
	}
}
