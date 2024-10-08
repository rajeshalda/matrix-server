<?php

namespace XF\Repository;

use XF\Entity\Admin;
use XF\Finder\AdminPermissionFinder;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class AdminPermissionRepository extends Repository
{
	/**
	 * @return Finder
	 */
	public function findPermissionsForList()
	{
		return $this->finder(AdminPermissionFinder::class)->order(['display_order']);
	}

	public function getPermissionTitlePairs()
	{
		return $this->findPermissionsForList()
			->fetch()
			->pluckNamed('title', 'admin_permission_id');
	}

	public function rebuildAdminPermissionCache()
	{
		$db = $this->em->getDb();
		$permissions = [];
		$permissionsSql = $db->query('
			SELECT admin_permission_entry.user_id, admin_permission_entry.admin_permission_id
			FROM xf_admin_permission_entry AS admin_permission_entry
			INNER JOIN xf_admin_permission AS admin_permission ON
				(admin_permission.admin_permission_id = admin_permission_entry.admin_permission_id)
		');
		while ($permission = $permissionsSql->fetch())
		{
			$permissions[$permission['user_id']][$permission['admin_permission_id']] = true;
		}

		/** @var Admin[] $admins */
		$admins = $this->em->findByIds(Admin::class, array_keys($permissions));
		foreach ($admins AS $admin)
		{
			$admin->permission_cache = $permissions[$admin->user_id];
			$admin->saveIfChanged();
		}
	}
}
