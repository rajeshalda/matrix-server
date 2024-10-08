<?php

namespace XF\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int $prompt_id
 *
 * RELATIONS
 * @property-read AbstractPrompt|null $Prompt
 */
abstract class AbstractPromptMap extends Entity
{
	public function getContainerId()
	{
		return $this->getValue(self::getContainerKey());
	}

	public static function getContainerKey()
	{
		throw new \LogicException('This must be overridden.');
	}

	protected static function setupDefaultStructure(Structure $structure, $table, $shortName, $promptIdentifier)
	{
		$containerKey = static::getContainerKey();

		$structure->table = $table;
		$structure->shortName = $shortName;
		$structure->primaryKey = [$containerKey, 'prompt_id'];
		$structure->columns = [
			$containerKey => ['type' => self::UINT, 'required' => true],
			'prompt_id' => ['type' => self::UINT, 'required' => true],
		];
		$structure->getters = [];
		$structure->relations = [
			'Prompt' => [
				'entity' => $promptIdentifier,
				'type' => self::TO_ONE,
				'conditions' => 'prompt_id',
				'primary' => true,
			],
		];

		return $structure;
	}
}
