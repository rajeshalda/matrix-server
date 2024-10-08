<?php

namespace XF\Pub\Controller;

use XF\ControllerPlugin\NodePlugin;
use XF\Entity\Category;
use XF\Entity\Node;
use XF\Entity\SessionActivity;
use XF\Finder\CategoryFinder;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Exception;
use XF\Repository\ForumRepository;
use XF\Repository\NodeRepository;
use XF\Repository\ThreadRepository;

use function count, intval, is_int, strval;

class CategoryController extends AbstractController
{
	public function actionIndex(ParameterBag $params)
	{
		$category = $this->assertViewableCategory($params->node_id ?: $params->node_name);

		$this->assertCanonicalUrl($this->buildLink('categories', $category));

		$nodeRepo = $this->getNodeRepo();
		$nodes = $nodeRepo->getNodeList($category->Node);
		$nodeTree = count($nodes) ? $nodeRepo->createNodeTree($nodes, $category->node_id) : null;
		$nodeExtras = $nodeTree ? $nodeRepo->getNodeListExtras($nodeTree) : null;

		$hasForumDescendents = false;
		if ($nodeTree)
		{
			$nodeTree->traverse(function ($id, Node $childNode) use (&$hasForumDescendents)
			{
				if ($childNode->node_type_id == 'Forum')
				{
					$hasForumDescendents = true;
				}
			});
		}

		$viewParams = [
			'category' => $category,
			'hasForumDescendents' => $hasForumDescendents,

			'nodeTree' => $nodeTree,
			'nodeExtras' => $nodeExtras,
		];
		return $this->view('XF:Category\View', 'category_view', $viewParams);
	}

	public function actionMarkRead(ParameterBag $params)
	{
		$category = $this->assertViewableCategory($params->node_id);

		$visitor = \XF::visitor();
		if (!$visitor->user_id)
		{
			return $this->noPermission();
		}

		$markDate = $this->filter('date', 'uint');
		if (!$markDate)
		{
			$markDate = \XF::$time;
		}

		$forumRepo = $this->repository(ForumRepository::class);

		if ($this->isPost())
		{
			$forumRepo->markForumTreeReadByVisitor($category, $markDate);

			return $this->redirect(
				$this->buildLink('categories', $category),
				\XF::phrase('forum_x_marked_as_read', ['forum' => $category->title])
			);
		}
		else
		{
			$viewParams = [
				'category' => $category,
				'date' => $markDate,
			];
			return $this->view('XF:Category\MarkRead', 'category_mark_read', $viewParams);
		}
	}

	/**
	 * @param $nodeId
	 * @param array $extraWith
	 *
	 * @return Category
	 *
	 * @throws Exception
	 */
	protected function assertViewableCategory($nodeIdOrName, array $extraWith = [])
	{
		if ($nodeIdOrName === null)
		{
			throw $this->exception($this->notFound(\XF::phrase('requested_forum_not_found')));
		}

		$visitor = \XF::visitor();
		$extraWith[] = 'Node.Permissions|' . $visitor->permission_combination_id;

		$finder = $this->em()->getFinder(CategoryFinder::class);
		$finder->with('Node', true)->with($extraWith);
		if (is_int($nodeIdOrName) || $nodeIdOrName === strval(intval($nodeIdOrName)))
		{
			$finder->where('node_id', $nodeIdOrName);
		}
		else
		{
			$finder->where(['Node.node_name' => $nodeIdOrName, 'Node.node_type_id' => 'Category']);
		}

		/** @var Category $category */
		$category = $finder->fetchOne();
		if (!$category)
		{
			throw $this->exception($this->notFound(\XF::phrase('requested_category_not_found')));
		}
		if (!$category->canView($error))
		{
			throw $this->exception($this->noPermission($error));
		}

		$this->plugin(NodePlugin::class)->applyNodeContext($category->Node);
		$this->setContentKey('category-' . $category->node_id);

		return $category;
	}

	/**
	 * @return NodeRepository
	 */
	protected function getNodeRepo()
	{
		return $this->repository(NodeRepository::class);
	}

	/**
	 * @return ThreadRepository
	 */
	protected function getThreadRepo()
	{
		return $this->repository(ThreadRepository::class);
	}

	/**
	 * @param SessionActivity[] $activities
	 */
	public static function getActivityDetails(array $activities)
	{
		return NodePlugin::getNodeActivityDetails(
			$activities,
			'Category',
			\XF::phrase('viewing_category')
		);
	}
}
