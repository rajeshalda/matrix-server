<?php

namespace XF\Admin\Controller;

use XF\ControllerPlugin\DeletePlugin;
use XF\Entity\Route;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Exception;
use XF\Repository\RouteRepository;

use function count;

class RouteController extends AbstractController
{
	/**
	 * @param $action
	 * @param ParameterBag $params
	 * @throws Exception
	 */
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertDevelopmentMode();
	}

	public function actionIndex()
	{
		$routeRepo = $this->getRouteRepo();
		$routes = $routeRepo->findRoutesForList()->fetch();

		$routeTypes = $routeRepo->getRouteTypes();

		$selectedTab = $this->filter('route_type', 'str');
		if (empty($selectedTab))
		{
			reset($routeTypes);
			$selectedTab = key($routeTypes);
		}

		$viewParams = [
			'routeTypes' => $routeTypes,
			'routesGrouped' => $routes->groupBy('route_type'),
			'selectedTab' => $selectedTab,
			'totalRoutes' => count($routes),
		];
		return $this->view('XF:Route\Listing', 'route_list', $viewParams);
	}

	protected function routeAddEdit(Route $route)
	{
		$viewParams = [
			'route' => $route,
			'routeTypes' => $this->getRouteRepo()->getRouteTypes(),
		];
		return $this->view('XF:Route\Edit', 'route_edit', $viewParams);
	}

	public function actionEdit(ParameterBag $params)
	{
		$route = $this->assertRouteExists($params['route_id']);
		return $this->routeAddEdit($route);
	}

	public function actionAdd()
	{
		$route = $this->em()->create(Route::class);
		$route->route_type = $this->filter('type', 'str');

		return $this->routeAddEdit($route);
	}

	protected function routeSaveProcess(Route $route)
	{
		$form = $this->formAction();

		$input = $this->filter([
			'route_type' => 'str',
			'route_prefix' => 'str',
			'sub_name' => 'str',
			'format' => 'str',
			'build_class' => 'str',
			'build_method' => 'str',
			'controller' => 'str',
			'context' => 'str',
			'action_prefix' => 'str',
			'addon_id' => 'str',
		]);
		// TODO: routing callbacks

		$form->basicEntitySave($route, $input);

		return $form;
	}

	public function actionSave(ParameterBag $params)
	{
		$this->assertPostOnly();

		if ($params['route_id'])
		{
			$route = $this->assertRouteExists($params['route_id']);
		}
		else
		{
			$route = $this->em()->create(Route::class);
		}

		$this->routeSaveProcess($route)->run();

		return $this->redirect($this->buildLink('routes', null, ['route_type' => $route->route_type]) . $this->buildLinkHash($route->route_id));
	}

	public function actionDelete(ParameterBag $params)
	{
		$route = $this->assertRouteExists($params->route_id);

		/** @var DeletePlugin $plugin */
		$plugin = $this->plugin(DeletePlugin::class);
		return $plugin->actionDelete(
			$route,
			$this->buildLink('routes/delete', $route),
			$this->buildLink('routes/edit', $route),
			$this->buildLink('routes'),
			$route->unique_name
		);
	}

	/**
	 * @param string $id
	 * @param array|string|null $with
	 * @param null|string $phraseKey
	 *
	 * @return Route
	 */
	protected function assertRouteExists($id, $with = null, $phraseKey = null)
	{
		return $this->assertRecordExists(Route::class, $id, $with, $phraseKey);
	}

	/**
	 * @return RouteRepository
	 */
	protected function getRouteRepo()
	{
		return $this->repository(RouteRepository::class);
	}
}
