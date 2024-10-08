<?php

namespace XF\Entity;

use XF\Finder\PhraseFinder;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Repository\AddOnRepository;
use XF\Repository\IconRepository;
use XF\Repository\LanguageRepository;
use XF\Repository\PhraseRepository;
use XF\Service\Phrase\CompileService;
use XF\Service\Phrase\GroupService;
use XF\Service\Phrase\RebuildService;

use function sizeof;

/**
 * COLUMNS
 * @property int|null $phrase_id
 * @property int $language_id
 * @property string $title
 * @property string $phrase_text
 * @property bool $global_cache
 * @property string $addon_id
 * @property int $version_id
 * @property string $version_string
 *
 * GETTERS
 * @property-read Language $Language
 *
 * RELATIONS
 * @property-read Language|null $Language_
 * @property-read AddOn|null $AddOn
 * @property-read Phrase|null $Master
 */
class Phrase extends Entity
{
	/**
	 * @return Language
	 */
	public function getLanguage()
	{
		if ($this->language_id == 0)
		{
			return $this->getLanguageRepo()->getMasterLanguage();
		}
		else
		{
			return $this->getRelation('Language');
		}
	}

	public function getPhraseGroup($new = true)
	{
		$title = $new ? $this->getValue('title') : $this->getExistingValue('title');
		if (!$title || !preg_match('/^([a-z0-9_]+)\./i', $title, $match))
		{
			return null;
		}

		return $match[1];
	}

	public function getApplicableLanguageIds()
	{
		if (!$this->phrase_id)
		{
			return [];
		}

		return $this->db()->fetchAllColumn("
			SELECT language_id
			FROM xf_phrase_map
			WHERE phrase_id = ?
		", $this->phrase_id);
	}

	protected function verifyTitle($title)
	{
		if (strpos($title, '.') !== false)
		{
			if (sizeof(explode('.', $title)) > 2)
			{
				$this->error(\XF::phrase('phrase_titles_may_only_contain_single_dot_character'));
				return false;
			}
			else if (substr($title, -1) === '.')
			{
				$this->error(\XF::phrase('phrase_titles_cannot_contain_dot_as_last_character'));
				return false;
			}
			else if (!preg_match('/^[a-z0-9_.]*$/i', $title))
			{
				$this->error(\XF::phrase('please_enter_title_using_only_alphanumeric_dot_underscore'));
				return false;
			}
			else if (!preg_match('/^[a-z0-9_]{1,50}\.[a-z0-9_]+$/i', $title))
			{
				$this->error(\XF::phrase('phrase_group_titles_must_be_no_more_than_50_characters'), 'title');
				return false;
			}
		}

		return true;
	}

	protected function _preSave()
	{
		if ($this->getOption('check_duplicate'))
		{
			if ($this->isChanged('title') || $this->isChanged('language_id'))
			{
				$phrase = $this->finder(PhraseFinder::class)->where('title', $this->title)->where('language_id', $this->language_id)->fetchOne();
				if ($phrase && $phrase != $this)
				{
					$this->error(\XF::phrase('phrase_titles_must_be_unique_in_language'), 'title');
				}
			}
		}

		if (!$this->isChanged('version_id') && $this->isChanged(['title', 'phrase_text', 'addon_id']))
		{
			$this->updateVersionId();
		}
	}

	protected function _postSave()
	{
		$rebuildService = $this->getRebuildPhraseService();
		$compileService = $this->getCompileService();

		$newPhraseGroup = $this->getPhraseGroup();
		$oldPhraseGroup = $this->getPhraseGroup(false);

		if ($this->isUpdate() && $this->isChanged('title'))
		{
			$compileService->deleteCompiled($this, false);
			if ($this->getOption('rebuild_map'))
			{
				$rebuildService->rebuildPhraseMapForTitle($this->getExistingValue('title'));
			}
			if ($this->getOption('recompile'))
			{
				$compileService->recompileByTitle($this->getExistingValue('title'));

				if ($oldPhraseGroup !== null)
				{
					$this->compilePhraseGroup($oldPhraseGroup);
				}
			}
			if ($this->getOption('recompile_include'))
			{
				$compileService->recompileIncludeContent($this->getExistingValue('title'));
			}
		}

		if ($this->getOption('rebuild_map') && ($this->isInsert() || $this->isChanged('title')))
		{
			$rebuildService->rebuildPhraseMapForTitle($this->title);
		}

		if ($this->getOption('rebuild_language_caches') && ($this->isInsert() || $this->isChanged(['global_cache', 'title', 'phrase_text'])))
		{
			$this->rebuildLanguageCache();
		}

		if ($this->isChanged(['title', 'phrase_text']))
		{
			if ($this->getOption('recompile'))
			{
				$compileService->recompile($this);

				if ($newPhraseGroup !== null)
				{
					$this->compilePhraseGroup($newPhraseGroup);
				}
			}
			if ($this->getOption('recompile_include'))
			{
				$compileService->recompileIncludeContent($this->title);
			}
		}

		if ($this->isChanged('phrase_text'))
		{
			$iconRepo = $this->repository(IconRepository::class);
			$iconRepo->enqueueUsageAnalyzer('phrase');
		}
	}

	protected function _postDelete()
	{
		$rebuildService = $this->getRebuildPhraseService();
		$compileService = $this->getCompileService();

		$compileService->deleteCompiled($this, false);
		if ($this->getOption('rebuild_map'))
		{
			$rebuildService->rebuildPhraseMapForTitle($this->title);
		}
		if ($this->getOption('recompile'))
		{
			$compileService->recompileByTitle($this->title);

			$phraseGroup = $this->getPhraseGroup();
			if ($phraseGroup !== null)
			{
				$this->compilePhraseGroup($phraseGroup);
			}
		}
		if ($this->getOption('recompile_include'))
		{
			$compileService->recompileIncludeContent($this->title);
		}
		if ($this->getOption('rebuild_language_caches'))
		{
			$this->rebuildLanguageCache();
		}
	}

	protected function compilePhraseGroup($group)
	{
		$groupService = $this->getGroupService();

		\XF::runOnce('compilePhraseGroup' . $group, function () use ($groupService, $group)
		{
			$groupService->compilePhraseGroup($group);
		});
	}

	protected function rebuildLanguageCache()
	{
		\XF::runOnce('languageCacheRebuild', function ()
		{
			$this->getLanguageRepo()->rebuildLanguageCache();
		});
	}

	protected function _setupDefaults()
	{
		/** @var AddOnRepository $addOnRepo */
		$addOnRepo = $this->_em->getRepository(AddOnRepository::class);
		$this->addon_id = $addOnRepo->getDefaultAddOnId();
	}

	public static function getStructure(Structure $structure)
	{
		$structure->table = 'xf_phrase';
		$structure->shortName = 'XF:Phrase';
		$structure->primaryKey = 'phrase_id';
		$structure->columns = [
			'phrase_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
			'language_id' => ['type' => self::UINT, 'required' => true],
			'title' => ['type' => self::BINARY, 'maxLength' => 100,
				'required' => 'please_enter_valid_title',
				'match' => self::MATCH_ALPHANUMERIC_DOT,
			],
			'phrase_text' => ['type' => self::STR, 'default' => ''],
			'global_cache' => ['type' => self::BOOL, 'default' => false],
			'addon_id' => ['type' => self::BINARY, 'maxLength' => 50, 'default' => ''],
			'version_id' => ['type' => self::UINT, 'default' => 0],
			'version_string' => ['type' => self::STR, 'maxLength' => 30, 'default' => ''],
		];
		$structure->behaviors = [
			'XF:DevOutputWritable' => [],
		];
		$structure->getters = [
			'Language' => true,
		];
		$structure->relations = [
			'Language' => [
				'type' => self::TO_ONE,
				'entity' => 'XF:Language',
				'conditions' => 'language_id',
				'primary' => true,
			],
			'AddOn' => [
				'entity' => 'XF:AddOn',
				'type' => self::TO_ONE,
				'conditions' => 'addon_id',
				'primary' => true,
			],
			'Master' => [
				'type' => self::TO_ONE,
				'entity' => 'XF:Phrase',
				'conditions' => [
					['title', '=', '$title'],
					['language_id', '=', '0'],
				],
				'primary' => false,
			],
		];
		$structure->options = [
			'recompile' => true,
			'recompile_include' => true,
			'rebuild_map' => true,
			'rebuild_language_caches' => true,
			'check_duplicate' => true,
		];

		return $structure;
	}

	/**
	 * @return RebuildService
	 */
	protected function getRebuildPhraseService()
	{
		return $this->app()->service(RebuildService::class);
	}

	/**
	 * @return CompileService
	 */
	protected function getCompileService()
	{
		return $this->app()->service(CompileService::class);
	}

	/**
	 * @return GroupService
	 */
	protected function getGroupService()
	{
		return $this->app()->service(GroupService::class);
	}

	/**
	 * @return PhraseRepository
	 */
	protected function getPhraseRepo()
	{
		return $this->repository(PhraseRepository::class);
	}

	/**
	 * @return LanguageRepository
	 */
	protected function getLanguageRepo()
	{
		return $this->repository(LanguageRepository::class);
	}
}
