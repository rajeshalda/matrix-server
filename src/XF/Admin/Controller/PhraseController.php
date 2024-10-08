<?php

namespace XF\Admin\Controller;

use XF\ControllerPlugin\LanguagePlugin;
use XF\Entity\Language;
use XF\Entity\Phrase;
use XF\Finder\PhraseFinder;
use XF\Mvc\ParameterBag;
use XF\Repository\LanguageRepository;
use XF\Repository\PhraseRepository;
use XF\Util\Arr;

use function count, intval;

class PhraseController extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertAdminPermission('language');
	}

	public function actionIndex()
	{
		$language = $this->plugin(LanguagePlugin::class)->getActiveEditLanguage();

		return $this->redirect($this->buildLink('languages/phrases', $language));
	}

	protected function phraseAddEdit(Phrase $phrase)
	{
		if ($phrase->exists() && !$this->request->exists('language_id'))
		{
			$languageId = $phrase->language_id;
		}
		else
		{
			$languageId = $this->filter('language_id', 'uint');
		}

		$language = $this->assertLanguageExists($languageId);
		if (!$language->canEdit())
		{
			return $this->error(\XF::phrase('phrases_in_this_language_can_not_be_modified'));
		}

		if (!$phrase->exists() && $language->language_id)
		{
			$phrase->addon_id = '';
		}

		$viewParams = [
			'phrase' => $phrase,
			'language' => $language,
			'redirect' => $this->getDynamicRedirect(),
		];
		return $this->view('XF:Phrase\Edit', 'phrase_edit', $viewParams);
	}

	public function actionEdit(ParameterBag $params)
	{
		$phrase = $this->assertPhraseExists($params['phrase_id']);
		return $this->phraseAddEdit($phrase);
	}

	public function actionEditByName()
	{
		$language = $this->plugin(LanguagePlugin::class)->getActiveEditLanguage();
		$phrase = $this->getPhraseRepo()->getEffectivePhraseByTitle($language, $this->filter('title', 'str'));

		return $this->redirect($this->buildLink('phrases/edit', $phrase, ['language_id' => $language->language_id]));
	}

	public function actionAdd()
	{
		$phrase = $this->em()->create(Phrase::class);

		if (empty($phrase->title) && $prefix = $this->filter('prefix', 'str'))
		{
			$phrase->set('title', $prefix);
		}

		return $this->phraseAddEdit($phrase);
	}

	protected function phraseSaveProcess(Phrase $phrase)
	{
		$form = $this->formAction();

		$input = $this->filter([
			'language_id' => 'uint',
			'title' => 'str',
			'phrase_text' => 'str,no-trim',
			'addon_id' => 'str',
			'global_cache' => 'bool',
		]);

		$form->setup(function () use ($phrase)
		{
			if ($phrase->language_id > 0)
			{
				$phrase->updateVersionId();
			}
		});

		$form->basicEntitySave($phrase, $input);

		return $form;
	}

	public function actionSave(ParameterBag $params)
	{
		$this->assertPostOnly();

		if ($params->phrase_id)
		{
			$phrase = $this->assertPhraseExists($params->phrase_id);

			$languageId = $this->filter('language_id', 'uint');
			if ($phrase->language_id != $languageId)
			{
				$phrase = $this->finder(PhraseFinder::class)->where([
					'language_id' => $languageId,
					'title' => $phrase->title,
				])->fetchOne();
				if (!$phrase)
				{
					$phrase = $this->em()->create(Phrase::class);
				}
			}
		}
		else
		{
			$phrase = $this->em()->create(Phrase::class);
		}

		$this->phraseSaveProcess($phrase)->run();

		$dynamicRedirect = $this->getDynamicRedirect('invalid', false);
		if ($dynamicRedirect == 'invalid' || !preg_match('#(languages|phrases)/#', $dynamicRedirect))
		{
			$dynamicRedirect = null;
		}

		if ($this->request->exists('exit'))
		{
			if ($dynamicRedirect)
			{
				$redirect = $dynamicRedirect;
			}
			else
			{
				$redirect = $this->buildLink('languages/phrases', $phrase->Language);
			}
			$redirect .= $this->buildLinkHash($phrase->phrase_id);
		}
		else
		{
			$redirect = $this->buildLink('phrases/edit', $phrase, ['_xfRedirect' => $dynamicRedirect]);
		}

		return $this->redirect($redirect);
	}

	public function actionTranslate(ParameterBag $params)
	{
		$this->assertPostOnly();

		$phrase = $this->assertPhraseExists($params->phrase_id);

		$languageId = $this->filter('language_id', 'uint');
		$language = $this->assertLanguageExists($languageId);
		if (!$language->canEdit())
		{
			return $this->error(\XF::phrase('phrases_in_this_language_can_not_be_modified'));
		}

		// If edit/skip, we display existing phrase regardless
		if ($phrase->language_id != $languageId && !$this->request->exists('edit') && !$this->request->exists('skip'))
		{
			$phrase = $this->finder(PhraseFinder::class)->where([
				'language_id' => $languageId,
				'title' => $phrase->title,
			])->fetchOne();
			if (!$phrase)
			{
				$phrase = $this->em()->create(Phrase::class);
			}
		}

		$viewClass = 'XF:Phrase\Translated';
		$templateName = 'phrase_translated';

		if ($phrase->isUpdate() && $this->request->exists('revert'))
		{
			$phrase->delete();
			$phrase = $this->getPhraseRepo()->getEffectivePhraseByTitle($language, $phrase->title);

			$message = \XF::phrase('your_changes_have_been_saved');
		}
		else if ($this->request->exists('skip'))
		{
			// No action required
			$message = \XF::phrase('no_changes_have_been_made_to_this_phrase');
		}
		else if ($this->request->exists('edit'))
		{
			// No action required either, but expand for editing
			$viewClass = 'XF:Phrase\Translate';
			$templateName = 'phrase_translate';
			$message = null;
		}
		else
		{
			$this->phraseSaveProcess($phrase)->run();
			$message = \XF::phrase('your_changes_have_been_saved');
		}

		$viewParams = [
			'phrase' => $phrase,
			'language' => $language,
		];
		$reply =  $this->view($viewClass, $templateName, $viewParams);
		if ($message)
		{
			$reply->setJsonParam('message', $message);
		}
		return $reply;
	}

	public function actionDelete(ParameterBag $params)
	{
		$phrase = $this->assertPhraseExists($params['phrase_id']);

		if ($this->isPost())
		{
			$phrase->delete();

			$redirect = $this->getDynamicRedirect('invalid', false);
			if ($redirect == 'invalid' || !preg_match('#(languages|phrases)/#', $redirect))
			{
				$redirect = $this->buildLink('languages/phrases', $phrase->Language);
			}

			return $this->redirect($redirect);
		}
		else
		{
			$viewParams = [
				'phrase' => $phrase,
			];
			return $this->view('XF:Phrase\Delete', 'phrase_delete', $viewParams);
		}
	}

	protected function filterSearchConditions()
	{
		return $this->filter([
			'addon_id' => 'str',
			'title' => 'str',
			'text' => 'str',
			'text_cs' => 'bool',
			'state' => 'array-str',
		]);
	}

	public function actionSearch()
	{
		$this->setSectionContext('searchPhrases');

		$languageRepo = $this->getLanguageRepo();

		if ($this->filter('search', 'uint'))
		{
			if ($this->request->exists('translate'))
			{
				return $this->rerouteController(self::class, 'translation');
			}

			$language = $this->assertLanguageExists($this->filter('language_id', 'uint'));
			if (!$language->canEdit())
			{
				return $this->error(\XF::phrase('phrases_in_this_language_can_not_be_modified'));
			}

			$filterer = $this->setupPhraseFilterer($language);
			$finder = $filterer->apply();

			$filterLinkParams = $filterer->getLinkParams();
			if (!$filterLinkParams)
			{
				return $this->redirect($this->buildLink('languages/phrases', $language));
			}

			$linkParams = [
				'search' => 1,
				'language_id' => $language->language_id,
			];
			$linkParams += $filterLinkParams;

			$total = $finder->total();

			if ($this->isPost() && $total > 0)
			{
				return $this->redirect($this->buildLink('phrases/search', null, $linkParams));
			}

			$finder->with('Phrase.AddOn');

			$phrases = $finder->fetch(1000); // limit this for performance reasons

			$viewParams = [
				'language' => $language,
				'phrases' => $phrases,
				'total' => $total,
				'linkParams' => $linkParams,
				'filterDisplay' => $filterer->getDisplayValues(),
			];
			return $this->view('XF:Phrase\SearchResults', 'phrase_search_results', $viewParams);
		}
		else
		{
			$viewParams = [
				'languageTree' => $languageRepo->getLanguageTree(),
				'languageId' => $this->plugin(LanguagePlugin::class)->getActiveLanguageId(),
			];
			return $this->view('XF:Phrase\Search', 'phrase_search', $viewParams);
		}
	}

	protected function setupPhraseFilterer(Language $language): \XF\Filterer\Phrase
	{
		/** @var \XF\Filterer\Phrase $filterer */
		$filterer = $this->app->filterer(\XF\Filterer\Phrase::class, ['language_id' => $language->language_id]);
		$filterer->addFilters($this->request, $this->filter('_skipFilter', 'str'));

		return $filterer;
	}

	public function actionTranslation()
	{
		$this->setSectionContext('searchPhrases');

		$language = $this->assertLanguageExists($this->filter('language_id', 'uint'));
		if (!$language->canEdit())
		{
			return $this->error(\XF::phrase('phrases_in_this_language_can_not_be_modified'));
		}

		$filterer = $this->setupPhraseFilterer($language);
		$finder = $filterer->apply();

		$linkParams = $filterer->getLinkParams();
		if (!$linkParams)
		{
			return $this->redirect($this->buildLink('languages/phrases', $language));
		}

		$total = $this->filter('total', 'uint');
		if (!$total)
		{
			$total = $finder->total();
		}

		$lastTitle = $this->filter('last_title', 'str');
		if ($lastTitle)
		{
			$expression = $finder->columnUtf8('title');
			$finder->where($expression, '>', $lastTitle);
		}

		$perPage = 50;

		$phrases = $finder->fetch($perPage);
		$last = $phrases->last();

		$count = $phrases->count() + $this->filter('last_count', 'uint');
		$hasMore = ($count < $total);

		$viewParams = [
			'language' => $language,
			'phrases' => $phrases,
			'last' => $last,
			'conditions' => $this->filterSearchConditions(),

			'perPage' => $perPage,
			'count' => $count,
			'total' => $total,
			'linkParams' => $linkParams,

			'hasMore' => $hasMore,


		];
		return $this->view('XF:Phrase\TranslateResults', 'phrase_translate_results', $viewParams);
	}

	public function actionRefineSearch()
	{
		$language = $this->assertLanguageExists($this->filter('language_id', 'uint'));
		if (!$language->canEdit())
		{
			$language = $this->plugin(LanguagePlugin::class)->getActiveEditLanguage();
		}

		$languageRepo = $this->getLanguageRepo();

		$filterer = $this->setupPhraseFilterer($language);

		$viewParams = [
			'language' => $language,
			'languageTree' => $languageRepo->getLanguageTree(),
			'conditions' => $filterer->getFiltersForForm(),
			'translateOnly' => $this->filter('translate_only', 'bool'),
		];
		return $this->view('XF:Phrase\RefineSearch', 'phrase_refine_search', $viewParams);
	}


	public function actionOutdated()
	{
		$this->setSectionContext('outdatedPhrases');

		$outdatedPhrases = $this->getPhraseRepo()->getOutdatedPhrases();
		$outdatedGrouped = Arr::arrayGroup(
			$outdatedPhrases,
			function ($v) { return $v['phrase']->language_id; }
		);

		$viewParams = [
			'outdatedPhrases' => $outdatedPhrases,
			'outdatedGrouped' => $outdatedGrouped,
			'languageTree' => $this->repository(LanguageRepository::class)->getLanguageTree(),
			'total' => count($outdatedPhrases),
		];
		return $this->view('XF:Phrase\Outdated', 'phrase_outdated', $viewParams);
	}

	protected function getActiveLanguageId()
	{
		$languageId = $this->request->getCookie('edit_language_id', null);
		if ($languageId === null)
		{
			$languageId = \XF::$developmentMode ? 0 : $this->options()->defaultLanguageId;
		}
		$languageId = intval($languageId);

		if ($languageId == 0 && !\XF::$developmentMode)
		{
			$languageId = $this->options()->defaultLanguageId;
		}

		return $languageId;
	}

	/**
	 * @param string $id
	 * @param array|string|null $with
	 * @param null|string $phraseKey
	 *
	 * @return Language
	 */
	protected function assertLanguageExists($id, $with = null, $phraseKey = null)
	{
		return $this->plugin(LanguagePlugin::class)->assertLanguageExists($id, $with, $phraseKey);
	}

	/**
	 * @param string $id
	 * @param array|string|null $with
	 * @param null|string $phraseKey
	 *
	 * @return Phrase
	 */
	protected function assertPhraseExists($id, $with = null, $phraseKey = null)
	{
		return $this->assertRecordExists(Phrase::class, $id, $with, $phraseKey);
	}

	/**
	 * @return LanguageRepository
	 */
	protected function getLanguageRepo()
	{
		return $this->repository(LanguageRepository::class);
	}

	/**
	 * @return PhraseRepository
	 */
	protected function getPhraseRepo()
	{
		return $this->repository(PhraseRepository::class);
	}
}
