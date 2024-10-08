<?php

namespace XF\Entity;

use XF\Job\ReactionDelete;
use XF\Job\ReactionScore;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Phrase;
use XF\Repository\ReactionRepository;

/**
 * COLUMNS
 * @property int|null $reaction_id
 * @property string $text_color
 * @property int $reaction_score
 * @property int $display_order
 * @property bool $active
 * @property string $emoji_shortname
 * @property string $image_url
 * @property string $image_url_2x
 * @property bool $sprite_mode
 * @property array $sprite_params
 *
 * GETTERS
 * @property-read Phrase $title
 * @property-read mixed $reaction_type
 * @property-read mixed $score_title
 * @property-read mixed $is_custom_score
 * @property-read string|null $emoji
 *
 * RELATIONS
 * @property-read \XF\Entity\Phrase|null $MasterTitle
 */
class Reaction extends Entity
{
	use EmojiIconTrait;
	use ImageSpriteTrait;

	public function canDelete(&$error = null)
	{
		if ($this->isDefaultReaction())
		{
			$error = \XF::phrase('it_is_not_possible_to_delete_default_reaction');
			return false;
		}

		return true;
	}

	public function canToggle(&$error = null)
	{
		if ($this->isDefaultReaction())
		{
			$error = \XF::phrase('it_is_not_possible_to_disable_default_reaction');
			return false;
		}

		return true;
	}

	public function canExport(&$error = null)
	{
		if ($this->isDefaultReaction())
		{
			$error = \XF::phrase('it_is_not_possible_to_export_default_reaction');
			return false;
		}

		return true;
	}

	public function getReactionType()
	{
		if ($this->reaction_score > 0)
		{
			return 'positive';
		}
		else if ($this->reaction_score < 0)
		{
			return 'negative';
		}
		else
		{
			return 'neutral';
		}
	}

	public function getScoreTitle()
	{
		return \XF::phrase('reaction_score.' . $this->reaction_type);
	}

	public function isDefaultReaction()
	{
		return ($this->reaction_id == 1);
	}

	public function isCustomScore()
	{
		return ($this->reaction_score > 1 || $this->reaction_score < -1);
	}

	/**
	 * @return Phrase
	 */
	public function getTitle()
	{
		return \XF::phrase($this->getPhraseName());
	}

	public function getPhraseName()
	{
		return 'reaction_title.' . $this->reaction_id;
	}

	public function getMasterPhrase()
	{
		$phrase = $this->MasterTitle;
		if (!$phrase)
		{
			$phrase = $this->_em->create(\XF\Entity\Phrase::class);
			$phrase->title = $this->_getDeferredValue(function () { return $this->getPhraseName(); }, 'save');
			$phrase->addon_id = '';
			$phrase->language_id = 0;
		}

		return $phrase;
	}

	protected function _preSave()
	{
		if ($this->isChanged('active') && !$this->active && !$this->canToggle($error))
		{
			$this->error($error);
		}

		if (!$this->emoji_shortname && !$this->image_url)
		{
			$this->error(\XF::phrase('please_enter_valid_emoji_short_name_or_image_url'));
		}

		if ($this->emoji_shortname && $this->image_url)
		{
			$this->error(\XF::phrase('please_enter_either_valid_emoji_short_name_or_image_url_but_not_both'));
		}
	}

	protected function _postSave()
	{
		$this->rebuildReactionCache();

		if ($this->isUpdate() && $this->isChanged('reaction_score'))
		{
			$this->app()->jobManager()->enqueueUnique('reactionChange' . $this->reaction_id, ReactionScore::class);
		}
	}

	protected function _preDelete()
	{
		if (!$this->canDelete($error))
		{
			$this->error($error);
		}
	}

	protected function _postDelete()
	{
		if ($this->MasterTitle)
		{
			$this->MasterTitle->delete();
		}

		$this->app()->jobManager()->enqueueUnique('reactionDelete' . $this->reaction_id, ReactionDelete::class, [
			'reaction_id' => $this->reaction_id,
			'reaction_score' => $this->reaction_score,
		]);

		$this->rebuildReactionCache();
	}

	protected function rebuildReactionCache()
	{
		$repo = $this->getReactionRepo();

		\XF::runOnce('reactionCache', function () use ($repo)
		{
			$repo->rebuildReactionCache();
			$repo->rebuildReactionSpriteCache();
		});
	}

	protected function _setupDefaults()
	{
		$this->sprite_params = ['w' => 32, 'h' => 32, 'x' => 0, 'y' => 0, 'bs' => ''];
	}

	public static function getStructure(Structure $structure)
	{
		$structure->table = 'xf_reaction';
		$structure->shortName = 'XF:Reaction';
		$structure->primaryKey = 'reaction_id';
		$structure->columns = [
			'reaction_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
			'text_color' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
			'reaction_score' => ['type' => self::INT, 'default' => 1],
			'display_order' => ['type' => self::UINT, 'default' => 10],
			'active' => ['type' => self::BOOL, 'default' => true],
		];
		$structure->getters = [
			'title' => true,
			'reaction_type' => true,
			'score_title' => true,
			'is_custom_score' => ['getter' => 'isCustomScore', 'cache' => false],
		];
		$structure->relations = [
			'MasterTitle' => [
				'entity' => 'XF:Phrase',
				'type' => self::TO_ONE,
				'conditions' => [
					['language_id', '=', 0],
					['title', '=', 'reaction_title.', '$reaction_id'],
				],
			],
		];
		$structure->options = [];

		static::addEmojiIconStructureElements($structure);
		static::addImageSpriteStructureElements($structure);
		$structure->columns['image_url']['required'] = false;

		return $structure;
	}

	/**
	 * @return ReactionRepository
	 */
	protected function getReactionRepo()
	{
		return $this->repository(ReactionRepository::class);
	}
}
