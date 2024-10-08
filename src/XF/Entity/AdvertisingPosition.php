<?php

namespace XF\Entity;

use XF\Behavior\DevOutputWritable;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Phrase;
use XF\Repository\AddOnRepository;
use XF\Repository\AdvertisingRepository;

/**
 * COLUMNS
 * @property string $position_id
 * @property array $arguments
 * @property string $addon_id
 * @property bool $active
 *
 * GETTERS
 * @property-read Phrase $title
 * @property-read Phrase $description
 *
 * RELATIONS
 * @property-read AddOn|null $AddOn
 * @property-read \XF\Entity\Phrase|null $MasterTitle
 * @property-read \XF\Entity\Phrase|null $MasterDescription
 */
class AdvertisingPosition extends Entity
{
	public function getTitlePhraseName()
	{
		return 'ad_pos.' . $this->position_id;
	}

	public function getDescriptionPhraseName()
	{
		return 'ad_pos_desc.' . $this->position_id;
	}

	/**
	 * @return Phrase
	 */
	public function getTitle()
	{
		return \XF::phrase($this->getTitlePhraseName());
	}

	/**
	 * @return Phrase
	 */
	public function getDescription()
	{
		return \XF::phrase($this->getDescriptionPhraseName());
	}

	public function getMasterTitlePhrase()
	{
		$phrase = $this->MasterTitle;
		if (!$phrase)
		{
			$phrase = $this->_em->create(\XF\Entity\Phrase::class);
			$phrase->title = $this->_getDeferredValue(function () { return $this->getTitlePhraseName(); });
			$phrase->language_id = 0;
			$phrase->addon_id = $this->_getDeferredValue(function () { return $this->addon_id; });
		}

		return $phrase;
	}

	public function getMasterDescriptionPhrase()
	{
		$phrase = $this->MasterDescription;
		if (!$phrase)
		{
			$phrase = $this->_em->create(\XF\Entity\Phrase::class);
			$phrase->title = $this->_getDeferredValue(function () { return $this->getDescriptionPhraseName(); });
			$phrase->language_id = 0;
			$phrase->addon_id = $this->_getDeferredValue(function () { return $this->addon_id; });
		}

		return $phrase;
	}

	protected function _postSave()
	{
		if ($this->isUpdate())
		{
			if ($this->isChanged('addon_id') || $this->isChanged('position_id'))
			{
				$writeDevOutput = $this->getBehavior(DevOutputWritable::class)->getOption('write_dev_output');

				/** @var Phrase $titlePhrase */
				$titlePhrase = $this->getExistingRelation('MasterTitle');
				if ($titlePhrase)
				{
					$titlePhrase->getBehavior(DevOutputWritable::class)->setOption('write_dev_output', $writeDevOutput);

					$titlePhrase->addon_id = $this->addon_id;
					$titlePhrase->title = $this->getTitlePhraseName();
					$titlePhrase->save();
				}

				/** @var Phrase $descriptionPhrase */
				$descriptionPhrase = $this->getExistingRelation('MasterDescription');
				if ($descriptionPhrase)
				{
					$descriptionPhrase->getBehavior(DevOutputWritable::class)->setOption('write_dev_output', $writeDevOutput);

					$descriptionPhrase->addon_id = $this->addon_id;
					$descriptionPhrase->title = $this->getDescriptionPhraseName();
					$descriptionPhrase->save();
				}

				if ($this->isChanged('position_id'))
				{
					$this->db()->update('xf_advertising', [
						'position_id' => $this->position_id,
					], 'position_id = ?', $this->getExistingValue('position_id'));
				}

				$this->writeAdsTemplate();
			}

			if ($this->isChanged(['arguments', 'active']))
			{
				$this->writeAdsTemplate();
			}
		}
		else if ($this->isInsert())
		{
			$this->writeAdsTemplate();
		}
	}

	protected function _postDelete()
	{
		$writeDevOutput = $this->getBehavior(DevOutputWritable::class)->getOption('write_dev_output');

		$titlePhrase = $this->MasterTitle;
		if ($titlePhrase)
		{
			$titlePhrase->getBehavior(DevOutputWritable::class)->setOption('write_dev_output', $writeDevOutput);

			$titlePhrase->delete();
		}
		$descriptionPhrase = $this->MasterDescription;
		if ($descriptionPhrase)
		{
			$descriptionPhrase->getBehavior(DevOutputWritable::class)->setOption('write_dev_output', $writeDevOutput);

			$descriptionPhrase->delete();
		}

		$this->db()->delete('xf_advertising', 'position_id = ?', $this->position_id);
		$this->writeAdsTemplate();
	}

	protected function _setupDefaults()
	{
		/** @var AddOnRepository $addOnRepo */
		$addOnRepo = $this->_em->getRepository(AddOnRepository::class);
		$this->addon_id = $addOnRepo->getDefaultAddOnId();
	}

	protected function writeAdsTemplate()
	{
		\XF::runOnce('writeAdsTemplate', function ()
		{
			$this->getAdvertisingRepo()->writeAdsTemplate();
		});
	}

	public static function getStructure(Structure $structure)
	{
		$structure->table = 'xf_advertising_position';
		$structure->shortName = 'XF:AdvertisingPosition';
		$structure->primaryKey = 'position_id';
		$structure->columns = [
			'position_id' => ['type' => self::STR, 'maxLength' => 50,
				'required' => 'please_enter_advertising_position_key',
				'unique' => 'advertising_position_keys_must_be_unique',
				'match' => self::MATCH_ALPHANUMERIC,
			],
			'arguments' => ['type' => self::JSON_ARRAY, 'default' => []],
			'addon_id' => ['type' => self::BINARY, 'maxLength' => 50, 'default' => ''],
			'active' => ['type' => self::BOOL, 'default' => true],
		];
		$structure->behaviors = [
			'XF:DevOutputWritable' => [],
		];
		$structure->getters = [
			'title' => true,
			'description' => true,
		];
		$structure->relations = [
			'AddOn' => [
				'entity' => 'XF:AddOn',
				'type' => self::TO_ONE,
				'conditions' => 'addon_id',
				'primary' => true,
			],
			'MasterTitle' => [
				'entity' => 'XF:Phrase',
				'type' => self::TO_ONE,
				'conditions' => [
					['language_id', '=', 0],
					['title', '=', 'ad_pos.', '$position_id'],
				],
			],
			'MasterDescription' => [
				'entity' => 'XF:Phrase',
				'type' => self::TO_ONE,
				'conditions' => [
					['language_id', '=', 0],
					['title', '=', 'ad_pos_desc.', '$position_id'],
				],
			],
		];
		$structure->options = [];

		return $structure;
	}

	/**
	 * @return AdvertisingRepository
	 */
	protected function getAdvertisingRepo()
	{
		return $this->repository(AdvertisingRepository::class);
	}
}
