<?php

namespace XF\EditHistory;

use XF\Entity\EditHistory;
use XF\Entity\Post;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Router;
use XF\Service\Post\EditorService;

class PostHandler extends AbstractHandler
{
	/**
	 * @param Post $content
	 */
	public function canViewHistory(Entity $content)
	{
		return ($content->canView() && $content->canViewHistory());
	}

	/**
	 * @param Post $content
	 */
	public function canRevertContent(Entity $content)
	{
		return $content->canEdit();
	}

	/**
	 * @param Post $content
	 */
	public function getContentText(Entity $content)
	{
		return $content->message;
	}

	/**
	 * @param Post $content
	 */
	public function getBreadcrumbs(Entity $content)
	{
		/** @var Router $router */
		$router = \XF::app()->container('router');

		$breadcrumbs = $content->Thread->Forum->getBreadcrumbs();
		$breadcrumbs[] = [
			'value' => $content->Thread->title,
			'href' => $router->buildLink('threads', $content->Thread),
		];
		return $breadcrumbs;
	}

	/**
	 * @param Post $content
	 */
	public function revertToVersion(Entity $content, EditHistory $history, ?EditHistory $previous = null)
	{
		/** @var EditorService $editor */
		$editor = \XF::app()->service(EditorService::class, $content);

		$editor->logEdit(false);
		$editor->setIsAutomated();
		$editor->setMessage($history->old_text);

		if (!$previous || $previous->edit_user_id != $content->user_id)
		{
			$content->last_edit_date = 0;
		}
		else if ($previous && $previous->edit_user_id == $content->user_id)
		{
			$content->last_edit_date = $previous->edit_date;
			$content->last_edit_user_id = $previous->edit_user_id;
		}

		return $editor->save();
	}

	public function getHtmlFormattedContent($text, ?Entity $content = null)
	{
		return \XF::app()->templater()->func('bb_code', [$text, 'post', $content]);
	}

	public function getSectionContext()
	{
		return 'forums';
	}

	public function getEntityWith()
	{
		$visitor = \XF::visitor();
		return ['Thread', 'Thread.Forum', 'Thread.Forum.Node.Permissions|' . $visitor->permission_combination_id];
	}
}
