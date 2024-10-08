<?php

namespace XF\Permission;

use XF\Entity\Node;
use XF\Entity\NodeType;
use XF\Entity\Permission;
use XF\Finder\NodeTypeFinder;
use XF\Mvc\Entity\Entity;
use XF\Repository\NodeRepository;

class NodePermissions extends TreeContentPermissions
{
	/** @var NodeType[]|null */
	protected $nodeTypes;

	protected $allowedGroups = null;

	public function getContentType()
	{
		return 'node';
	}

	public function getAnalysisTypeTitle()
	{
		return \XF::phrase('node_permissions');
	}

	public function getContentTitle(Entity $entity)
	{
		return $entity->title;
	}

	public function isValidPermission(Permission $permission)
	{
		if ($this->allowedGroups === null)
		{
			$this->setupNodeTypes();
		}

		if (isset($this->allowedGroups[$permission->permission_group_id]))
		{
			return true;
		}

		if ($permission->permission_group_id == 'general' && $permission->permission_id == 'viewNode')
		{
			return true;
		}

		return false;
	}

	protected function setupNodeTypes()
	{
		$this->nodeTypes = \XF::em()->getFinder(NodeTypeFinder::class)->fetch();

		$allowed = [];
		foreach ($this->nodeTypes AS $nodeType)
		{
			if ($nodeType->permission_group_id)
			{
				$allowed[$nodeType->permission_group_id] = true;
			}
		}

		$this->allowedGroups = $allowed;
	}

	protected function setupBuildTypeData()
	{
		parent::setupBuildTypeData();

		$this->setupNodeTypes();
	}

	public function getContentTree()
	{
		/** @var NodeRepository $nodeRepo */
		$nodeRepo = $this->builder->em()->getRepository(NodeRepository::class);
		return $nodeRepo->createNodeTree($nodeRepo->getFullNodeList());
	}

	protected function getFinalPerms($contentId, array $calculated, array &$childPerms)
	{
		/** @var Node $node */
		$node = $this->tree->getData($contentId);
		if ($node && isset($this->nodeTypes[$node->node_type_id]))
		{
			$groupId = $this->nodeTypes[$node->node_type_id]->permission_group_id;
		}
		else
		{
			$groupId = null;
		}

		if (!$groupId)
		{
			return [];
		}

		if (!isset($calculated[$groupId]))
		{
			$calculated[$groupId] = [];
		}
		$calculated[$groupId]['view'] = $calculated['general']['viewNode'];

		$final = $this->builder->finalizePermissionValues($calculated[$groupId]);

		if (!$final['view'])
		{
			$childPerms['general']['viewNode'] = 'deny';
		}

		return $final;
	}

	protected function getFinalAnalysisPerms($contentId, array $calculated, array &$childPerms)
	{
		/** @var Node $node */
		$node = $this->tree->getData($contentId);
		if ($node && isset($this->nodeTypes[$node->node_type_id]))
		{
			$groupId = $this->nodeTypes[$node->node_type_id]->permission_group_id;
		}
		else
		{
			$groupId = null;
		}

		if (!$groupId)
		{
			return [];
		}

		$finalize = [
			'general' => ['viewNode' => $calculated['general']['viewNode']],
		];
		if (isset($calculated[$groupId]))
		{
			$finalize[$groupId] = $calculated[$groupId];
		}
		$final = $this->builder->finalizePermissionValues($finalize);

		if (!$final['general']['viewNode'])
		{
			$childPerms['general']['viewNode'] = 'deny';
		}

		return $final;
	}
}
