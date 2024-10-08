<?php

namespace XF\AddOn\DataType;

use XF\Behavior\DevOutputWritable;

class HelpPage extends AbstractDataType
{
	public function getShortName()
	{
		return 'XF:HelpPage';
	}

	public function getContainerTag()
	{
		return 'help_pages';
	}

	public function getChildTag()
	{
		return 'help_page';
	}

	public function exportAddOnData($addOnId, \DOMElement $container)
	{
		$entries = $this->finder()
			->where('addon_id', $addOnId)
			->order('page_id')->fetch();

		foreach ($entries AS $entry)
		{
			$node = $container->ownerDocument->createElement($this->getChildTag());
			$this->exportMappedAttributes($node, $entry);
			$container->appendChild($node);
		}

		return $entries->count() ? true : false;
	}

	public function importAddOnData($addOnId, \SimpleXMLElement $container, $start = 0, $maxRunTime = 0)
	{
		$startTime = microtime(true);

		$entries = $this->getEntries($container, $start);
		if (!$entries)
		{
			return false;
		}

		$ids = $this->pluckXmlAttribute($entries, 'page_id');
		$existing = $this->findByIds($ids);

		$i = 0;
		$last = 0;
		foreach ($entries AS $entry)
		{
			$id = $ids[$i++];

			if ($i <= $start)
			{
				continue;
			}

			$entity = $existing[$id] ?? $this->create();
			$entity->getBehavior(DevOutputWritable::class)->setOption('write_dev_output', false);

			$this->importMappedAttributes($entry, $entity);
			$entity->addon_id = $addOnId;
			$entity->save(true, false);

			if ($this->resume($maxRunTime, $startTime))
			{
				$last = $i;
				break;
			}
		}
		return ($last ?: false);
	}

	public function deleteOrphanedAddOnData($addOnId, \SimpleXMLElement $container)
	{
		$this->deleteOrphanedSimple($addOnId, $container, 'page_id');
	}

	protected function getMappedAttributes()
	{
		return [
			'page_id',
			'page_name',
			'display_order',
			'callback_class',
			'callback_method',
			'advanced_mode',
			'active',
		];
	}

	protected function getMaintainedAttributes()
	{
		return [
			'display_order',
			'active',
		];
	}
}
