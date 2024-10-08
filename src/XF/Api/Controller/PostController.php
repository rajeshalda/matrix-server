<?php

namespace XF\Api\Controller;

use XF\Api\ControllerPlugin\ContentVotePlugin;
use XF\Api\ControllerPlugin\ReactionPlugin;
use XF\Entity\Post;
use XF\Mvc\Entity\Entity;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Exception;
use XF\Repository\PostRepository;
use XF\Repository\ThreadRepository;
use XF\Service\Post\DeleterService;
use XF\Service\Post\EditorService;
use XF\Service\ThreadQuestion\MarkSolutionService;

/**
 * @api-group Posts
 */
class PostController extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertApiScopeByRequestMethod('thread');
	}

	/**
	 * @api-desc Gets information about the specified post
	 *
	 * @api-out Post $post
	 */
	public function actionGet(ParameterBag $params)
	{
		$post = $this->assertViewablePost($params->post_id, 'api|thread');

		$result = $post->toApiResult(Entity::VERBOSITY_VERBOSE, [
			'with_thread' => true,
		]);

		return $this->apiResult(['post' => $result]);
	}

	/**
	 * @api-desc Updates the specified post
	 *
	 * @api-in str $message
	 * @api-in bool $silent If true and permissions allow, this edit will not be updated with a "last edited" indication
	 * @api-in bool $clear_edit If true and permissions allow, any "last edited" indication will be removed. Requires "silent".
	 * @api-in bool $author_alert
	 * @api-in str $author_alert_reason
	 * @api-in str $attachment_key API attachment key to upload files. Attachment key context type must be post with context[post_id] set to this post ID.
	 *
	 * @api-out true $success
	 * @api-out Post $post
	 */
	public function actionPost(ParameterBag $params)
	{
		$post = $this->assertViewablePost($params->post_id);

		if (\XF::isApiCheckingPermissions() && !$post->canEdit($error))
		{
			return $this->noPermission($error);
		}

		$editor = $this->setupPostEdit($post);

		if (\XF::isApiCheckingPermissions())
		{
			$editor->checkForSpam();
		}

		if (!$editor->validate($errors))
		{
			return $this->error($errors);
		}

		$editor->save();

		return $this->apiSuccess([
			'post' => $post->toApiResult(Entity::VERBOSITY_VERBOSE),
		]);
	}

	/**
	 * @param Post $post
	 *
	 * @return EditorService
	 */
	protected function setupPostEdit(Post $post)
	{
		$input = $this->filter([
			'message' => '?str',
			'silent' => 'bool',
			'clear_edit' => 'bool',
			'author_alert' => 'bool',
			'author_alert_reason' => 'str',
			'attachment_key' => 'str',
		]);

		/** @var EditorService $editor */
		$editor = $this->service(EditorService::class, $post);

		if ($input['message'] !== null)
		{
			if ($input['silent'] && (\XF::isApiBypassingPermissions() || $post->canEditSilently()))
			{
				$editor->logEdit(false);
				if ($input['clear_edit'])
				{
					$post->last_edit_date = 0;
				}
			}

			$editor->setMessage($input['message']);
		}

		if (\XF::isApiBypassingPermissions() || $post->Thread->Forum->canUploadAndManageAttachments())
		{
			$hash = $this->getAttachmentTempHashFromKey($input['attachment_key'], 'post', ['post_id' => $post->post_id]);
			$editor->setAttachmentHash($hash);
		}

		if ($input['author_alert'] && $post->canSendModeratorActionAlert())
		{
			$editor->setSendAlert(true, $input['author_alert_reason']);
		}

		return $editor;
	}

	/**
	 * @api-desc Deletes the specified post. Default to soft deletion.
	 *
	 * @api-in bool $hard_delete
	 * @api-in str $reason
	 * @api-in bool $author_alert
	 * @api-in str $author_alert_reason
	 *
	 * @api-out true $success
	 */
	public function actionDelete(ParameterBag $params)
	{
		$post = $this->assertViewablePost($params->post_id);

		if (\XF::isApiCheckingPermissions() && !$post->canDelete('soft', $error))
		{
			return $this->noPermission($error);
		}

		$type = 'soft';
		$reason = $this->filter('reason', 'str');

		if ($this->filter('hard_delete', 'bool'))
		{
			$this->assertApiScope('thread:delete_hard');

			if (\XF::isApiCheckingPermissions() && !$post->canDelete('hard', $error))
			{
				return $this->noPermission($error);
			}

			$type = 'hard';
		}

		/** @var DeleterService $deleter */
		$deleter = $this->service(DeleterService::class, $post);

		if ($this->filter('author_alert', 'bool') && $post->canSendModeratorActionAlert())
		{
			$deleter->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
		}

		$deleter->delete($type, $reason);

		return $this->apiSuccess();
	}

	/**
	 * @api-desc Reacts to the specified post
	 *
	 * @api-see \XF\Api\ControllerPlugin\Reaction::actionReact()
	 */
	public function actionPostReact(ParameterBag $params)
	{
		$post = $this->assertViewablePost($params->post_id);

		/** @var ReactionPlugin $reactPlugin */
		$reactPlugin = $this->plugin(ReactionPlugin::class);
		return $reactPlugin->actionReact($post);
	}

	/**
	 * @api-desc Votes on the specified post (if applicable)
	 *
	 * @api-see \XF\Api\ControllerPlugin\ContentVote::actionVote()
	 */
	public function actionPostVote(ParameterBag $params)
	{
		$post = $this->assertViewablePost($params->post_id);

		/** @var ContentVotePlugin $votePlugin */
		$votePlugin = $this->plugin(ContentVotePlugin::class);
		return $votePlugin->actionVote($post);
	}

	/**
	 * @api-desc Toggle the specified post as the solution to its containing thread. If a post is marked as a solution when another is already marked, the existing solution will be unmarked.
	 *
	 * @api-out true Success
	 * @api-out Post|null $new_solution_post A post that was marked as the solution
	 * @api-out Post|null $old_solution_post A post that was un-marked as the solution
	 */
	public function actionPostMarkSolution(ParameterBag $params)
	{
		$post = $this->assertViewablePost($params->post_id);

		if (\XF::isApiCheckingPermissions() && !$post->canMarkAsQuestionSolution($error))
		{
			return $this->noPermission($error);
		}

		$thread = $post->Thread;
		$existingSolution = $thread->Question->Solution ?? null;

		/** @var MarkSolutionService $markSolution */
		$markSolution = $this->service(MarkSolutionService::class, $thread);

		$apiResult = [
			'old_solution_post' => null,
			'new_solution_post' => null,
		];

		if ($existingSolution && $post->post_id == $existingSolution->post_id)
		{
			$markSolution->unmarkSolution();

			$apiResult['old_solution_post'] = $existingSolution->toApiResult(Entity::VERBOSITY_VERBOSE);
		}
		else
		{
			$markSolution->markSolution($post);

			$apiResult['new_solution_post'] = $post->toApiResult(Entity::VERBOSITY_VERBOSE);
			if ($existingSolution)
			{
				$apiResult['old_solution_post'] = $existingSolution->toApiResult(Entity::VERBOSITY_VERBOSE);
			}
		}

		return $this->apiSuccess($apiResult);
	}

	/**
	 * @param int $id
	 * @param string|array $with
	 *
	 * @return Post
	 *
	 * @throws Exception
	 */
	protected function assertViewablePost($id, $with = 'api')
	{
		return $this->assertViewableApiRecord(Post::class, $id, $with);
	}

	/**
	 * @return ThreadRepository
	 */
	protected function getThreadRepo()
	{
		return $this->repository(ThreadRepository::class);
	}

	/**
	 * @return PostRepository
	 */
	protected function getPostRepo()
	{
		return $this->repository(PostRepository::class);
	}
}
