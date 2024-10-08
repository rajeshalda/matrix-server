<?php

namespace XF\Pub\Controller;

use XF\Entity\Search;
use XF\Entity\User;
use XF\Http\Request;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Repository\SearchRepository;
use XF\Repository\UserRepository;
use XF\Search\Query\KeywordQuery;
use XF\Util\Arr;

use function is_array;

class SearchController extends AbstractController
{
	/**
	 * @var int
	 */
	protected const MAX_AUTO_COMPLETE_RESULTS = 8;

	public function actionIndex(ParameterBag $params)
	{
		if ($params->search_id && !$this->filter('searchform', 'bool'))
		{
			return $this->rerouteController(self::class, 'results', $params);
		}

		$this->assertNotEmbeddedImageRequest();

		$visitor = \XF::visitor();
		if (!$visitor->canSearch($error))
		{
			return $this->noPermission($error);
		}

		$input = $this->convertShortSearchInputNames();
		$input = $this->mergeInputFromSearchMenu($input);

		$searcher = $this->app->search();
		$type = $input['search_type'] ?: $this->filter('type', 'str');

		$viewParams = [
			'tabs' => $searcher->getSearchTypeTabs(),
			'type' => $type,
			'isRelevanceSupported' => $searcher->isRelevanceSupported(),
			'input' => $input,
		];

		$typeHandler = null;
		if ($type && $searcher->isValidContentType($type))
		{
			$typeHandler = $searcher->handler($type);
			if (!$typeHandler->getSearchFormTab())
			{
				$typeHandler = null;
			}
		}

		if ($typeHandler)
		{
			if ($sectionContext = $typeHandler->getSectionContext())
			{
				$this->setSectionContext($sectionContext);
			}

			$viewParams = array_merge($viewParams, $typeHandler->getSearchFormData());
			$templateName = $typeHandler->getTypeFormTemplate();
		}
		else
		{
			$viewParams['type'] = '';
			$templateName = 'search_form_all';
		}

		$viewParams['formTemplateName'] = $templateName;

		return $this->view('XF:Search\Form', 'search_form', $viewParams);
	}

	public function actionAutoComplete(ParameterBag $params): AbstractReply
	{
		$this->assertPostOnly();

		if (!\XF::visitor()->canSearch($error))
		{
			return $this->noPermission($error);
		}

		$suggestEnabled = $this->options()->searchSuggestions['enabled'];
		if (!$suggestEnabled)
		{
			return $this->noPermission();
		}

		$searcher = $this->app->search();
		if (!$searcher->isAutoCompleteSupported())
		{
			return $this->noPermission();
		}

		$input = $this->getSearchInput();

		$query = $this->prepareSearchQuery($input, $constraints);
		if ($query->getErrors())
		{
			return $this->error($query->getErrors());
		}

		if ($searcher->isQueryEmpty($query, $error))
		{
			return $this->error($error);
		}

		$results = $searcher->autoComplete(
			$query,
			static::MAX_AUTO_COMPLETE_RESULTS
		);
		$resultSet = $searcher->getResultSet($results)->limitToViewableResults();
		$q = $query->getKeywords();
		$autoCompleteResults = $searcher->getAutoCompleteResults($resultSet, [
			'q' => $q,
		]);

		$viewParams = [
			'results' => $autoCompleteResults,
			'q' => $q,
		];
		return $this->view('XF:Search\AutoComplete', '', $viewParams);
	}

	/**
	 * @param $input
	 *
	 * @return array|mixed
	 */
	protected function mergeInputFromSearchMenu($input)
	{
		if ($this->request->exists('from_search_menu'))
		{
			$menuInput = $this->getSearchInput();
			// TODO: if the menu input says something like 'this thread', get its parent forum maybe?

			return array_replace_recursive($input, $menuInput);
		}

		return $input;
	}

	public function actionSearch()
	{
		$this->assertNotEmbeddedImageRequest();

		if ($this->request->exists('from_search_menu'))
		{
			return $this->rerouteController(self::class, 'index');
		}

		$visitor = \XF::visitor();
		if (!$visitor->canSearch($error))
		{
			return $this->noPermission($error);
		}

		$input = $this->getSearchInput();

		$query = $this->prepareSearchQuery($input, $constraints);

		if ($query->getErrors())
		{
			return $this->error($query->getErrors());
		}

		$searcher = $this->app->search();
		if ($searcher->isQueryEmpty($query, $error))
		{
			return $this->error($error);
		}

		return $this->runSearch($query, $constraints);
	}

	protected function getSearchInput()
	{
		$filters = $this->getSearchInputFilters();

		$input = $this->filter($filters);

		$constraintInput = $this->filter('constraints', 'json-array');
		foreach ($filters AS $k => $type)
		{
			if (isset($constraintInput[$k]))
			{
				$cleaned = $this->app->inputFilterer()->filter($constraintInput[$k], $type);
				if (is_array($cleaned))
				{
					$input[$k] = array_merge($input[$k], $cleaned);
				}
				else
				{
					$input[$k] = $cleaned;
				}
			}
		}

		return $input;
	}

	protected function getSearchInputFilters()
	{
		return [
			'search_type' => 'str',
			'keywords' => 'str',
			'c' => 'array',
			'grouped' => 'bool',
			'order' => '?str',
		];
	}

	public function actionResults(ParameterBag $params)
	{
		$this->assertNotEmbeddedImageRequest();

		$visitor = \XF::visitor();

		/** @var Search $search */
		$search = $this->em()->find(Search::class, $params->search_id);
		if (!$search || $search->user_id != $visitor->user_id || !$visitor->user_id)
		{
			$searchData = $this->convertShortSearchInputNames();
			$query = $this->prepareSearchQuery($searchData, $constraints);
			if ($query->getErrors())
			{
				return $this->notFound();
			}

			if ($visitor->user_id)
			{
				// always re-run search for logged-in users
				return $this->runSearch($query, $constraints);
			}
			else if (!$search || ($search->search_query && $search->search_query !== $this->filter('q', 'str')))
			{
				return $this->notFound();
			}
		}

		$page = $this->filterPage();
		$perPage = $this->options()->searchResultsPerPage;

		$this->assertValidPage($page, $perPage, $search->result_count, 'search', $search);

		$searcher = $this->app()->search();
		$resultSet = $searcher->getResultSet($search->search_results);

		$resultSet->sliceResultsToPage($page, $perPage);

		if (!$resultSet->countResults())
		{
			return $this->message(\XF::phrase('no_results_found'));
		}

		$maxPage = ceil($search->result_count / $perPage);

		if ($search->search_order == 'date'
			&& $search->result_count > $perPage
			&& $page == $maxPage)
		{
			$lastResult = $resultSet->getLastResultData($lastResultType);
			$getOlderResultsDate = $searcher->handler($lastResultType)->getResultDate($lastResult);
		}
		else
		{
			$getOlderResultsDate = null;
		}

		$resultOptions = [
			'search' => $search,
			'term' => $search->search_query,
		];
		$resultsWrapped = $searcher->wrapResultsForRender($resultSet, $resultOptions);

		$modTypes = [];
		foreach ($resultsWrapped AS $wrapper)
		{
			$handler = $wrapper->getHandler();
			$entity = $wrapper->getResult();
			if ($handler->canUseInlineModeration($entity))
			{
				$type = $handler->getContentType();
				if (!isset($modTypes[$type]))
				{
					$modTypes[$type] = $this->app->getContentTypePhrase($type);
				}
			}
		}

		$mod = $this->filter('mod', 'str');
		if ($mod && !isset($modTypes[$mod]))
		{
			$mod = '';
		}

		$viewParams = [
			'search' => $search,
			'results' => $resultsWrapped,

			'page' => $page,
			'perPage' => $perPage,

			'modTypes' => $modTypes,
			'activeModType' => $mod,

			'getOlderResultsDate' => $getOlderResultsDate,
		];
		return $this->view('XF:Search\Results', 'search_results', $viewParams);
	}

	public function actionMember()
	{
		$this->assertNotEmbeddedImageRequest();

		$userId = $this->filter('user_id', 'uint');
		$user = $this->assertRecordExists(User::class, $userId, null, 'requested_member_not_found');

		$constraints = ['users' => $user->username];

		$searcher = $this->app->search();
		$query = $searcher->getQuery();
		$query->byUserId($user->user_id)
			->orderedBy('date');

		$content = $this->filter('content', 'str');
		$type = $this->filter('type', 'str');
		if ($content && $searcher->isValidContentType($content))
		{
			$typeHandler = $searcher->handler($content);
			$query->forTypeHandlerBasic($typeHandler);
			// this applies the type limits that make sense

			$constraints['content'] = $content;

			$grouped = $this->filter('grouped', 'bool');
			if ($grouped)
			{
				$query->withGroupedResults();
			}
		}
		else if ($type && $searcher->isValidContentType($type))
		{
			$query->inType($type);
			$constraints['type'] = $type;
		}

		$threadType = $this->filter('thread_type', 'str');
		if ($threadType && $query->getTypes() == ['thread'])
		{
			$query->withMetadata('thread_type', $threadType);
			$constraints['thread_type'] = $threadType;
		}

		$before = $this->filter('before', 'uint');
		if ($before)
		{
			$query->olderThan($before);
		}

		return $this->runSearch($query, $constraints, false);
	}

	public function actionOlder(ParameterBag $params)
	{
		$this->assertNotEmbeddedImageRequest();

		/** @var Search $search */
		$search = $this->em()->find(Search::class, $params->search_id);
		if (!$search || $search->user_id != \XF::visitor()->user_id)
		{
			return $this->notFound();
		}

		$searchData = $this->convertSearchToQueryInput($search);
		$searchData['c']['older_than'] = $this->filter('before', 'uint');

		$query = $this->prepareSearchQuery($searchData, $constraints);
		if ($query->getErrors())
		{
			return $this->error($query->getErrors());
		}

		return $this->runSearch($query, $constraints);
	}

	protected function convertShortSearchInputNames()
	{
		return $this->convertShortSearchNames($this->filter([
			't' => 'str',
			'q' => 'str',
			'c' => 'array',
			'g' => 'bool',
			'o' => 'str',
		]));
	}

	protected function convertShortSearchNames(array $input)
	{
		$output = [];

		if (isset($input['t']))
		{
			$output['search_type'] = $input['t'] ?: null;
		}

		if (isset($input['q']))
		{
			$output['keywords'] = $input['q'];
		}

		if (isset($input['c']))
		{
			$output['c'] = $input['c'];
		}

		if (isset($input['g']))
		{
			$output['grouped'] = $input['g'] ? 1 : 0;
		}

		if (isset($input['o']))
		{
			$output['order'] = $input['o'] ?: null;
		}

		return $output;
	}

	protected function convertSearchToQueryInput(Search $search)
	{
		return [
			'search_type' => $search->search_type,
			'keywords' => $search->search_query,
			'c' => $search->search_constraints,
			'grouped' => $search->search_grouping ? 1 : 0,
			'order' => $search->search_order,
		];
	}

	protected function prepareSearchQuery(array $data, &$urlConstraints = [])
	{
		$searchRequest = new Request($this->app->inputFilterer(), $data, [], []);
		$input = $searchRequest->filter([
			'search_type' => 'str',
			'keywords' => 'str',
			'c' => 'array',
			'c.title_only' => 'uint',
			'c.newer_than' => 'datetime',
			'c.older_than' => 'datetime',
			'c.users' => 'str',
			'c.content' => 'str',
			'c.type' => 'str',
			'c.thread_type' => 'str',
			'grouped' => 'bool',
			'order' => 'str',
		]);

		$urlConstraints = $input['c'];

		$searcher = $this->app()->search();
		$query = $searcher->getQuery();

		if ($input['search_type'] && $searcher->isValidContentType($input['search_type']))
		{
			$typeHandler = $searcher->handler($input['search_type']);
			$query->forTypeHandler($typeHandler, $searchRequest, $urlConstraints);
		}

		if ($input['grouped'])
		{
			$query->withGroupedResults();
		}

		$input['keywords'] = $this->app->stringFormatter()->censorText($input['keywords'], '');
		if ($input['keywords'])
		{
			$query->withKeywords($input['keywords'], $input['c.title_only']);
		}

		if ($input['c.newer_than'])
		{
			$query->newerThan($input['c.newer_than']);
		}
		else
		{
			unset($urlConstraints['newer_than']);
		}
		if ($input['c.older_than'])
		{
			$query->olderThan($input['c.older_than']);
		}
		else
		{
			unset($urlConstraints['older_than']);
		}

		if ($input['c.users'])
		{
			$users = Arr::stringToArray($input['c.users'], '/,\s*/');
			if ($users)
			{
				/** @var UserRepository $userRepo */
				$userRepo = $this->repository(UserRepository::class);
				$matchedUsers = $userRepo->getUsersByNames($users, $notFound);
				if ($notFound)
				{
					$query->error(
						'users',
						\XF::phrase('following_members_not_found_x', ['members' => implode(', ', $notFound)])
					);
				}
				else
				{
					$query->byUserIds($matchedUsers->keys());
					$urlConstraints['users'] = implode(', ', $users);
				}
			}
		}

		if ($input['c.content'])
		{
			$query->inType($input['c.content']);
		}
		else if ($input['c.type'])
		{
			$query->inType($input['c.type']);
		}

		if ($input['c.thread_type'] && $query->getTypes() == ['thread'])
		{
			$query->withMetadata('thread_type', $input['c.thread_type']);
		}

		if ($input['order'])
		{
			$query->orderedBy($input['order']);
		}

		return $query;
	}

	protected function runSearch(KeywordQuery $query, array $constraints, $allowCached = true)
	{
		$visitor = \XF::visitor();
		if (!$visitor->canSearch($error))
		{
			return $this->noPermission($error);
		}

		/** @var SearchRepository $searchRepo */
		$searchRepo = $this->repository(SearchRepository::class);
		$search = $searchRepo->runSearch($query, $constraints, $allowCached);

		if (!$search)
		{
			return $this->message(\XF::phrase('no_results_found'));
		}

		return $this->redirect($this->buildLink('search', $search), '');
	}

	public static function getActivityDetails(array $activities)
	{
		return \XF::phrase('searching');
	}
}
