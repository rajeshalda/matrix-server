<?php

namespace XF\Api\Controller;

use XF\Api\ControllerPlugin\UserPlugin;
use XF\Entity\User;
use XF\Finder\UserFinder;
use XF\Mvc\Entity\Entity;
use XF\Mvc\ParameterBag;
use XF\Repository\UserRepository;
use XF\Util\Str;

/**
 * @api-group Users
 */
class UsersController extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertApiScopeByRequestMethod('user');
	}

	/**
	 * @api-desc Gets a list of users (alphabetically)
	 *
	 * @api-in int $page
	 *
	 * @api-out User[] $users
	 * @api-out pagination $pagination
	 */
	public function actionGet()
	{
		$visitor = \XF::visitor();

		// always let user admins get a full list
		if (\XF::isApiCheckingPermissions() && !$visitor->hasAdminPermission('user'))
		{
			if (!$this->options()->enableMemberList || !$visitor->canViewMemberList())
			{
				return $this->noPermission();
			}
		}

		$page = $this->filterPage();
		$perPage = $this->options()->membersPerPage;

		/** @var UserFinder $finder */
		$finder = $this->finder(UserFinder::class);
		$finder->isValidUser()
			->with('api')
			->setDefaultOrder('username', 'asc')
			->limitByPage($page, $perPage);

		// TODO: allow filtering and sorting options for admins

		$total = $finder->total();
		$this->assertValidApiPage($page, $perPage, $total);

		$users = $finder->fetch();

		return $this->apiResult([
			'users' => $users->toApiResults(),
			'pagination' => $this->getPaginationData($users, $page, $perPage, $total),
		]);
	}

	/**
	 * @api-desc Finds users by a prefix of their user name.
	 *
	 * @api-in str $username <required>
	 *
	 * @api-out User|null $exact The user that matched the given username exactly
	 * @api-out User[] $recommendations A list of users that match the prefix of the username (but not exactly)
	 */
	public function actionGetFindName()
	{
		$this->assertRequiredApiInput('username');

		$username = ltrim($this->filter('username', 'str', ['no-trim']));

		if ($username !== '' && Str::strlen($username) >= 2)
		{
			/** @var UserFinder $userFinder */
			$userFinder = $this->finder(UserFinder::class);

			$recommendations = $userFinder
				->where('username', 'like', $userFinder->escapeLike($username, '?%'))
				->isValidUser(true)
				->with('api')
				->order('username')
				->fetch(10);
		}
		else
		{
			$recommendations = $this->em()->getEmptyCollection();
		}

		$exact = null;
		if ($username !== '')
		{
			$exact = $this->em()->findOne(User::class, ['username' => $username], 'api');
			if ($exact && $recommendations)
			{
				unset($recommendations[$exact->user_id]);
			}
		}

		return $this->apiResult([
			'exact' => $exact ? $exact->toApiResult(Entity::VERBOSITY_VERBOSE) : null,
			'recommendations' => $recommendations->toApiResults(),
		]);
	}

	/**
	 * @api-desc Finds users by their email. Only available to admin users (or when bypassing permissions).
	 *
	 * @api-in str $email <required>
	 *
	 * @api-out User|null $user The user that matched the given email exactly
	 */
	public function actionGetFindEmail()
	{
		$this->assertAdminPermission('user');
		$this->assertRequiredApiInput('email');

		$email = $this->filter('email', 'str');
		$user = $this->em()->findOne(User::class, ['email' => $email], 'api');

		return $this->apiResult([
			'user' => $user ? $user->toApiResult(Entity::VERBOSITY_VERBOSE) : null,
		]);
	}

	/**
	 * @api-desc Creates a user.
	 *
	 * @api-see \XF\Api\ControllerPlugin\User::userSaveProcessAdmin()
	 *
	 * @api-out true $success
	 * @api-out User $user
	 */
	public function actionPost()
	{
		$this->assertAdminPermission('user');
		$this->assertRequiredApiInput(['username', 'password']);

		$user = $this->getUserRepo()->setupBaseUser();

		/** @var UserPlugin $userPlugin */
		$userPlugin = $this->plugin(UserPlugin::class);
		$userPlugin->userSaveProcessAdmin($user)->run();

		return $this->apiSuccess([
			'user' => $user->toApiResult(Entity::VERBOSITY_VERBOSE),
		]);
	}

	/**
	 * @return UserRepository
	 */
	protected function getUserRepo()
	{
		return $this->repository(UserRepository::class);
	}
}
