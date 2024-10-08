<?php

namespace XF\Repository;

use XF\Entity\User;
use XF\Finder\UserFinder;
use XF\Mvc\Entity\ArrayCollection;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;
use XF\Util\Str;

use function array_key_exists, count, intval, is_string;

class UserRepository extends Repository
{
	public static $guestPermissionCombinationId = 1;

	/**
	 * @param integer $userId
	 * @param array $with
	 *
	 * @return User
	 */
	public function getVisitor($userId, array $with = [])
	{
		if ($userId)
		{
			$with = $this->getVisitorWith($with);
			$user = $this->em->find(User::class, $userId, $with);
		}
		else
		{
			$user = null;
		}

		$user = $user ?: $this->getGuestUser();

		$this->app()->fire('visitor_setup', [&$user]);

		return $user;
	}

	public function getVisitorWith(array $with = [])
	{
		$with[] = 'Admin';
		$with[] = 'Option';
		$with[] = 'Profile';
		$with[] = 'Privacy';
		$with[] = 'PermissionCombination';

		$this->app()->fire('visitor_extra_with', [&$with]);

		return $with;
	}

	/**
	 * @return User
	 */
	public function getPreRegActionUser()
	{
		$manipulator = function (array $data)
		{
			$preRegActionOption = $this->options()->preRegAction;

			if ($preRegActionOption['enabled'] && $preRegActionOption['permissionCombinationId'])
			{
				$data['permission_combination_id'] = $preRegActionOption['permissionCombinationId'];

				$userGroupId = array_shift($preRegActionOption['userGroups']);

				$data['user_group_id'] = intval($userGroupId);
				$data['secondary_group_ids'] = implode(',', $preRegActionOption['userGroups']);
				$data['user_id'] = 2 ** 32 - 1; // max user ID, shouldn't be taken
			}

			return $data;
		};

		return $this->getGuestUser(null, $manipulator);
	}

	/**
	 * @param string|null $username
	 * @param \Closure|null $defaultDataManipulator
	 *
	 * @return User
	 */
	public function getGuestUser($username = null, ?\Closure $defaultDataManipulator = null)
	{
		$structure = $this->em->getEntityStructure(User::class);

		$data = [
			'entity' => 'XF:User',
			'values' => [],
			'relations' => [],
		];

		$defaultGuestData = $this->getGuestDefaultData();
		if ($defaultDataManipulator)
		{
			$defaultGuestData = $defaultDataManipulator($defaultGuestData);
		}

		$relationsData = $defaultGuestData['_relations'];
		unset($defaultGuestData['_relations']);

		if (is_string($username))
		{
			$defaultGuestData['username'] = $username;
		}

		$vf = $this->em->getValueFormatter();

		foreach ($structure->columns AS $name => $column)
		{
			if (array_key_exists($name, $defaultGuestData))
			{
				$data['values'][$name] = $defaultGuestData[$name];
			}
			else if (array_key_exists('default', $column))
			{
				// when instantiating an entity, values are source encoded, but the default values aren't, so encode them
				$data['values'][$name] = $vf->encodeValueForSource($column['type'], $column['default']);
			}
		}

		foreach ($structure->relations AS $name => $relation)
		{
			if (array_key_exists($name, $relationsData))
			{
				$data['relations'][$name] = [
					'entity' => $relation['entity'],
					'values' => $relationsData[$name],
					'relations' => [],
				];
			}
		}

		$user = $this->_hydrateGuestUserData($data);
		$user->setReadOnly(true);

		// have to ensure this isn't attached otherwise it will match user_id = 0 lookups
		$this->em->detachEntity($user);

		return $user;
	}

	protected function getGuestDefaultData()
	{
		$options = $this->options();

		// Note: the data here should be specified in *source encoded* form (what would be stored in the DB).
		// This is passed into the guest entity as if it comes from the DB.

		$defaultData = [
			'user_id' => 0,
			'username' => '',
			'permission_combination_id' => self::$guestPermissionCombinationId,
			'user_group_id' => User::GROUP_GUEST,
			'secondary_group_ids' => '',
			'timezone' => $options ? $options->guestTimeZone : 'Europe/London',

			'_relations' => [
				'Option' => [
					'user_id' => 0,
					'content_show_signature' => $options ? $options->guestShowSignatures : false,
				],
				'Profile' => [
					'user_id' => 0,
				],
				'Privacy' => [
					'user_id' => 0,
				],
				'Auth' => [
					'user_id' => 0,
				],
			],
		];

		$this->app()->fire('visitor_guest_setup', [&$defaultData]);

		return $defaultData;
	}

	protected function _hydrateGuestUserData(array $data)
	{
		$relations = [];
		foreach ($data['relations'] AS $name => $subData)
		{
			$relations[$name] = $this->_hydrateGuestUserData($subData);
		}

		return $this->em->instantiateEntity($data['entity'], $data['values'], $relations);
	}

	/**
	 * Ensures that the base fields/relationships are all set to make a "valid" user
	 * once saved.
	 *
	 * @param User|null $user An existing user to check against or nothing to create a new one
	 *
	 * @return User
	 */
	public function setupBaseUser(?User $user = null)
	{
		if (!$user)
		{
			$user = $this->em->create(User::class);
		}

		$option = $user->getRelationOrDefault('Option', true);
		$option->hydrateRelation('User', $user);

		$profile = $user->getRelationOrDefault('Profile', true);
		$profile->hydrateRelation('User', $user);

		$privacy = $user->getRelationOrDefault('Privacy', true);
		$privacy->hydrateRelation('User', $user);

		$auth = $user->getRelationOrDefault('Auth', true);
		$auth->hydrateRelation('User', $user);

		return $user;
	}

	/**
	 * @param $nameOrEmail
	 * @param array $with
	 *
	 * @return User
	 */
	public function getUserByNameOrEmail($nameOrEmail, array $with = [])
	{
		if (strpos($nameOrEmail, '@'))
		{
			/** @var User $user */
			$user = $this->em->findOne(User::class, ['email' => $nameOrEmail], $with);
			if ($user)
			{
				return $user;
			}
		}

		/** @var User $user */
		$user = $this->em->findOne(User::class, ['username' => $nameOrEmail], $with);

		return $user;
	}

	/**
	 * @param array $usernames
	 * @param array $notFound
	 * @param array $with
	 * @param bool $validOnly
	 * @param array $extraWhere
	 *
	 * @return ArrayCollection
	 */
	public function getUsersByNames(array $usernames, &$notFound = [], $with = [], $validOnly = false, $extraWhere = [])
	{
		$usernames = array_map('trim', $usernames);
		foreach ($usernames AS $key => $username)
		{
			if ($username === '')
			{
				unset($usernames[$key]);
			}
		}

		$notFound = [];

		if (!$usernames)
		{
			return $this->em->getEmptyCollection();
		}

		$finder = $this->finder(UserFinder::class)
			->where('username', $usernames)
			->with($with);
		if ($validOnly)
		{
			$finder->isValidUser();
		}
		if ($extraWhere)
		{
			$finder->where($extraWhere);
		}

		$users = $finder->fetch();
		if ($users->count() != count($usernames))
		{
			$usernamesLower = array_map('strtolower', $usernames);
			$notFound = $usernames;

			foreach ($users AS $user)
			{
				do
				{
					$foundKey = array_search(strtolower($user['username']), $usernamesLower, true);
					if ($foundKey !== false)
					{
						unset($notFound[$foundKey]);
						unset($usernamesLower[$foundKey]);
					}
				}
				while ($foundKey !== false);
			}
		}

		//return $users;

		$orderedUsers = [];
		foreach ($usernames AS $searchUsername)
		{
			$searchUsername = Str::normalize(Str::strtolower($searchUsername));
			foreach ($users AS $id => $user)
			{
				$testUsername = Str::normalize(Str::strtolower($user->username));
				if ($searchUsername == $testUsername && !isset($orderedUsers[$id]))
				{
					$orderedUsers[$id] = $user;
				}
			}
		}
		foreach ($users AS $id => $user)
		{
			if (!isset($orderedUsers[$id]))
			{
				$orderedUsers[$id] = $user;
			}
		}

		return $this->em->getBasicCollection($orderedUsers);
	}

	public function getUsersByIdsOrdered(array $ids, $with = [])
	{
		if (!$ids)
		{
			return $this->em->getEmptyCollection();
		}

		$users = $this->em->findByIds(User::class, $ids, $with);
		return $users->sortByList($ids);
	}

	/**
	 * @return Finder
	 */
	public function findValidUsers()
	{
		return $this->finder(UserFinder::class)->isValidUser();
	}

	public function findRecentlyActiveValidUsers()
	{
		return $this->finder(UserFinder::class)->isValidUser(true);
	}

	/**
	 * @return null|User
	 */
	public function getLatestValidUser()
	{
		return $this->findValidUsers()->order('register_date', 'DESC')->fetchOne();
	}
}
