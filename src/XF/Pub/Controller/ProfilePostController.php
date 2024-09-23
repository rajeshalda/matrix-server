<?php

namespace XF\Pub\Controller;

use XF\ControllerPlugin\EditorPlugin;
use XF\ControllerPlugin\InlineModPlugin;
use XF\ControllerPlugin\IpPlugin;
use XF\ControllerPlugin\ReactionPlugin;
use XF\ControllerPlugin\ReportPlugin;
use XF\ControllerPlugin\UndeletePlugin;
use XF\ControllerPlugin\WarnPlugin;
use XF\Entity\ProfilePost;
use XF\Entity\ProfilePostComment;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Finder;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Exception;
use XF\Mvc\RouteMatch;
use XF\Repository\AttachmentRepository;
use XF\Repository\ProfilePostRepository;
use XF\Service\ProfilePost\DeleterService;
use XF\Service\ProfilePost\EditorService;
use XF\Service\ProfilePostComment\ApproverService;
use XF\Service\ProfilePostComment\CreatorService;

class ProfilePostController extends AbstractController
{
	use EmbedResolverTrait;

	public function actionIndex(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);

		if ($this->filter('_xfWithData', 'bool'))
		{
			$this->request->set('_xfDisableInlineMod', true);
			return $this->rerouteController(self::class, 'show', $params);
		}

		$profilePostRepo = $this->getProfilePostRepo();

		$profilePostFinder = $profilePostRepo->findProfilePostsOnProfile($profilePost->ProfileUser);
		$profilePostsTotal = $profilePostFinder->where('post_date', '>', $profilePost->post_date)->total();

		$page = floor($profilePostsTotal / $this->options()->messagesPerPage) + 1;

		return $this->redirectPermanently(
			$this->buildLink('members', $profilePost->ProfileUser, ['page' => $page]) . '#profile-post-' . $profilePost->profile_post_id
		);
	}

	public function actionShow(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);

		$profilePostRepo = $this->getProfilePostRepo();
		$profilePost = $profilePostRepo->addCommentsToProfilePost($profilePost);

		/** @var AttachmentRepository $attachmentRepo */
		$attachmentRepo = $this->repository(AttachmentRepository::class);
		$attachmentRepo->addAttachmentsToContent([$profilePost->profile_post_id => $profilePost], 'profile_post');

		if ($profilePost->canUploadAndManageAttachments())
		{
			$profilePostAttachData = [$profilePost->profile_post_id => $attachmentRepo->getEditorData('profile_post_comment', $profilePost)];
		}

		$viewParams = [
			'profilePost' => $profilePost,
			'showTargetUser' => true,
			'canInlineMod' => $profilePost->canUseInlineModeration(),
			'allowInlineMod' => !$this->request->get('_xfDisableInlineMod'),
			'profilePostAttachData' => $profilePostAttachData ?? [],
		];
		return $this->view('XF:ProfilePost\Show', 'profile_post', $viewParams);
	}

	public function actionEdit(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);
		if (!$profilePost->canEdit($error))
		{
			return $this->noPermission($error);
		}

		$noInlineMod = $this->filter('_xfNoInlineMod', 'bool');

		if ($this->isPost())
		{
			$editor = $this->setupEdit($profilePost);
			$editor->checkForSpam();

			if (!$editor->validate($errors))
			{
				return $this->error($errors);
			}
			$editor->save();

			$this->finalizeEdit($editor);

			if ($this->filter('_xfWithData', 'bool') && $this->filter('_xfInlineEdit', 'bool'))
			{
				$profilePosts = [$profilePost->profile_post_id => $profilePost];

				/** @var AttachmentRepository $attachmentRepo */
				$attachmentRepo = $this->repository(AttachmentRepository::class);
				$attachmentRepo->addAttachmentsToContent($profilePosts, 'profile_post');

				if ($profilePost->canUploadAndManageAttachments())
				{
					$attachmentData = $attachmentRepo->getEditorData('profile_post_comment', $profilePost);
				}
				else
				{
					$attachmentData = null;
				}

				$viewParams = [
					'profilePost' => $profilePost,

					'noInlineMod' => $noInlineMod,

					'attachmentData' => $attachmentData,
				];
				$reply = $this->view('XF:ProfilePost\EditNewProfilePost', 'profile_post_edit_new_post', $viewParams);
				$reply->setJsonParam('message', \XF::phrase('your_changes_have_been_saved'));
				return $reply;
			}
			else
			{
				return $this->redirect($this->buildLink('profile-posts', $profilePost));
			}
		}
		else
		{
			if ($profilePost->ProfileUser->canUploadAndManageAttachmentsOnProfile())
			{
				/** @var AttachmentRepository $attachmentRepo */
				$attachmentRepo = $this->repository(AttachmentRepository::class);
				$attachmentData = $attachmentRepo->getEditorData('profile_post', $profilePost);
			}
			else
			{
				$attachmentData = null;
			}

			$viewParams = [
				'profilePost' => $profilePost,
				'profileUser' => $profilePost->ProfileUser,

				'quickEdit' => $this->filter('_xfWithData', 'bool'),
				'noInlineMod' => $noInlineMod,

				'attachmentData' => $attachmentData,
			];
			return $this->view('XF:ProfilePost\Edit', 'profile_post_edit', $viewParams);
		}
	}

	public function actionDelete(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);
		if (!$profilePost->canDelete('soft', $error))
		{
			return $this->noPermission($error);
		}

		if ($this->isPost())
		{
			$type = $this->filter('hard_delete', 'bool') ? 'hard' : 'soft';
			$reason = $this->filter('reason', 'str');

			if (!$profilePost->canDelete($type, $error))
			{
				return $this->noPermission($error);
			}

			/** @var DeleterService $deleter */
			$deleter = $this->service(DeleterService::class, $profilePost);

			if ($this->filter('author_alert', 'bool') && $profilePost->canSendModeratorActionAlert())
			{
				$deleter->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
			}

			$deleter->delete($type, $reason);

			$this->plugin(InlineModPlugin::class)->clearIdFromCookie('profile_post', $profilePost->profile_post_id);

			return $this->redirect(
				$this->getDynamicRedirect($this->buildLink('members', $profilePost->ProfileUser), false)
			);
		}
		else
		{
			$viewParams = [
				'profilePost' => $profilePost,
				'profileUser' => $profilePost->ProfileUser,
			];
			return $this->view('XF:ProfilePost\Delete', 'profile_post_delete', $viewParams);
		}
	}

	public function actionUndelete(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);

		/** @var UndeletePlugin $plugin */
		$plugin = $this->plugin(UndeletePlugin::class);
		return $plugin->actionUndelete(
			$profilePost,
			$this->buildLink('profile-posts/undelete', $profilePost),
			$this->buildLink('profile-posts', $profilePost),
			\XF::phrase('profile_post_by_x', ['name' => $profilePost->username]),
			'message_state'
		);
	}

	public function actionIp(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);
		$breadcrumbs = $this->getProfilePostBreadcrumbs($profilePost);

		/** @var IpPlugin $ipPlugin */
		$ipPlugin = $this->plugin(IpPlugin::class);
		return $ipPlugin->actionIp($profilePost, $breadcrumbs);
	}

	public function actionReport(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);
		if (!$profilePost->canReport($error))
		{
			return $this->noPermission($error);
		}

		/** @var ReportPlugin $reportPlugin */
		$reportPlugin = $this->plugin(ReportPlugin::class);
		return $reportPlugin->actionReport(
			'profile_post',
			$profilePost,
			$this->buildLink('profile-posts/report', $profilePost),
			$this->buildLink('profile-posts', $profilePost)
		);
	}

	public function actionReact(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);

		/** @var ReactionPlugin $reactionPlugin */
		$reactionPlugin = $this->plugin(ReactionPlugin::class);
		return $reactionPlugin->actionReactSimple($profilePost, 'profile-posts');
	}

	public function actionReactions(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);

		$breadcrumbs = $this->getProfilePostBreadcrumbs($profilePost);

		/** @var ReactionPlugin $reactionPlugin */
		$reactionPlugin = $this->plugin(ReactionPlugin::class);
		return $reactionPlugin->actionReactions(
			$profilePost,
			'profile-posts/reactions',
			null,
			$breadcrumbs
		);
	}

	public function actionWarn(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);

		if (!$profilePost->canWarn($error))
		{
			return $this->noPermission($error);
		}

		$breadcrumbs = $this->getProfilePostBreadcrumbs($profilePost);

		/** @var WarnPlugin $warnPlugin */
		$warnPlugin = $this->plugin(WarnPlugin::class);
		return $warnPlugin->actionWarn(
			'profile_post',
			$profilePost,
			$this->buildLink('profile-posts/warn', $profilePost),
			$breadcrumbs
		);
	}

	/**
	 * @param ProfilePost $profilePost
	 *
	 * @return CreatorService
	 */
	protected function setupProfilePostComment(ProfilePost $profilePost)
	{
		$message = $this->plugin(EditorPlugin::class)->fromInput('message');

		/** @var CreatorService $creator */
		$creator = $this->service(CreatorService::class, $profilePost);
		$creator->setContent($message);

		if ($profilePost->canUploadAndManageAttachments())
		{
			$creator->setAttachmentHash($this->filter('attachment_hash', 'str'));
		}

		return $creator;
	}

	protected function finalizeProfilePostComment(CreatorService $creator)
	{
		$creator->sendNotifications();
	}

	public function actionAddComment(ParameterBag $params)
	{
		$this->assertPostOnly();

		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);
		if (!$profilePost->canComment($error))
		{
			return $this->noPermission($error);
		}

		$creator = $this->setupProfilePostComment($profilePost);
		$creator->checkForSpam();

		if (!$creator->validate($errors))
		{
			return $this->error($errors);
		}
		$this->assertNotFlooding('post');
		$comment = $creator->save();

		$this->finalizeProfilePostComment($creator);

		if ($this->filter('_xfWithData', 'bool') && $this->request->exists('last_date') && $profilePost->canView())
		{
			$profilePostRepo = $this->getProfilePostRepo();

			$lastDate = $this->filter('last_date', 'uint');

			/** @var Finder $profilePostCommentList */
			$profilePostCommentList = $profilePostRepo->findNewestCommentsForProfilePost($profilePost, $lastDate);
			$profilePostComments = $profilePostCommentList->fetch();

			// put the posts into oldest-first order
			$profilePostComments = $profilePostComments->reverse(true);

			$viewParams = [
				'profilePost' => $profilePost,
				'profilePostComments' => $profilePostComments,
			];
			$view = $this->view('XF:Member\NewProfilePostComments', 'profile_post_new_profile_post_comments', $viewParams);
			$view->setJsonParam('lastDate', $profilePostComments->last()->comment_date);
			return $view;
		}
		else
		{
			return $this->redirect($this->buildLink('profile-posts/comments', $comment));
		}
	}

	public function actionLoadPrevious(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);

		$repo = $this->getProfilePostRepo();

		$comments = $repo->findProfilePostComments($profilePost)
			->with('full')
			->where('comment_date', '<', $this->filter('before', 'uint'))
			->order('comment_date', 'DESC')
			->limit(20)
			->fetch()
			->reverse();

		if ($comments->count())
		{
			$firstCommentDate = $comments->first()->comment_date;

			$moreCommentsFinder = $repo->findProfilePostComments($profilePost)
				->where('comment_date', '<', $firstCommentDate);

			$loadMore = ($moreCommentsFinder->total() > 0);
		}
		else
		{
			$firstCommentDate = 0;
			$loadMore = false;
		}

		$viewParams = [
			'profilePost' => $profilePost,
			'comments' => $comments,
			'firstCommentDate' => $firstCommentDate,
			'loadMore' => $loadMore,
		];
		return $this->view('XF:ProfilePost\LoadPrevious', 'profile_post_comments', $viewParams);
	}

	public function actionComments(ParameterBag $params)
	{
		$comment = $this->assertViewableComment($params->profile_post_comment_id);
		$profilePost = $this->assertViewableProfilePost($comment->profile_post_id);

		$profilePostRepo = $this->getProfilePostRepo();

		$profilePostFinder = $profilePostRepo->findProfilePostsOnProfile($profilePost->ProfileUser);
		$profilePostsTotal = $profilePostFinder->where('post_date', '>', $profilePost->post_date)->total();

		$page = floor($profilePostsTotal / $this->options()->messagesPerPage) + 1;

		$commentId = $comment->profile_post_comment_id;
		$anchor = '#profile-post-comment-' . $commentId;
		if (!isset($profilePost->latest_comment_ids[$commentId]))
		{
			$anchor = '#profile-post-' . $profilePost->profile_post_id;
		}

		return $this->redirectPermanently(
			$this->buildLink('members', $profilePost->ProfileUser, ['page' => $page]) . $anchor
		);
	}

	public function actionCommentsShow(ParameterBag $params)
	{
		$comment = $this->assertViewableComment($params->profile_post_comment_id);

		$viewParams = [
			'comment' => $comment,
			'profilePost' => $comment->ProfilePost,
		];
		return $this->view('XF:ProfilePost\Comments\Show', 'profile_post_comment', $viewParams);
	}

	public function actionCommentsEdit(ParameterBag $params)
	{
		$comment = $this->assertViewableComment($params->profile_post_comment_id);
		if (!$comment->canEdit($error))
		{
			return $this->noPermission($error);
		}

		/** @var AttachmentRepository $attachmentRepo */
		$attachmentRepo = $this->repository(AttachmentRepository::class);

		if ($this->isPost())
		{
			$editor = $this->setupCommentEdit($comment);
			$editor->checkForSpam();

			if (!$editor->validate($errors))
			{
				return $this->error($errors);
			}
			$editor->save();

			$this->finalizeCommentEdit($editor);

			if ($this->filter('_xfWithData', 'bool') && $this->filter('_xfInlineEdit', 'bool'))
			{


				$viewParams = [
					'profilePost' => $comment->ProfilePost,
					'comment' => $comment,
				];
				$reply = $this->view('XF:ProfilePost\Comments\EditNewComment', 'profile_post_comment_edit_new_comment', $viewParams);
				$reply->setJsonParam('message', \XF::phrase('your_changes_have_been_saved'));
				return $reply;
			}
			else
			{
				return $this->redirect($this->buildLink('profile-posts/comments', $comment));
			}
		}
		else
		{
			$profilePost = $comment->ProfilePost;

			if ($profilePost->canUploadAndManageAttachments())
			{
				$attachmentData = $attachmentRepo->getEditorData('profile_post_comment', $comment);
			}
			else
			{
				$attachmentData = null;
			}

			$viewParams = [
				'comment' => $comment,
				'profilePost' => $profilePost,
				'quickEdit' => $this->responseType() == 'json',
				'attachmentData' => $attachmentData,
			];
			return $this->view('XF:ProfilePost\Comments\Edit', 'profile_post_comment_edit', $viewParams);
		}
	}

	public function actionCommentsDelete(ParameterBag $params)
	{
		$comment = $this->assertViewableComment($params->profile_post_comment_id);
		if (!$comment->canDelete('soft', $error))
		{
			return $this->noPermission($error);
		}

		if ($this->isPost())
		{
			$type = $this->filter('hard_delete', 'bool') ? 'hard' : 'soft';
			$reason = $this->filter('reason', 'str');

			if (!$comment->canDelete($type, $error))
			{
				return $this->noPermission($error);
			}

			/** @var \XF\Service\ProfilePostComment\DeleterService $deleter */
			$deleter = $this->service(\XF\Service\ProfilePostComment\DeleterService::class, $comment);

			if ($this->filter('author_alert', 'bool') && $comment->canSendModeratorActionAlert())
			{
				$deleter->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
			}

			$deleter->delete($type, $reason);

			return $this->redirect(
				$this->getDynamicRedirect($this->buildLink('profile-posts', $comment), false)
			);
		}
		else
		{
			$viewParams = [
				'comment' => $comment,
				'profilePost' => $comment->ProfilePost,
			];
			return $this->view('XF:ProfilePost\Comments\Delete', 'profile_post_comment_delete', $viewParams);
		}
	}

	public function actionCommentsUndelete(ParameterBag $params)
	{
		$comment = $this->assertViewableComment($params->profile_post_comment_id);

		/** @var UndeletePlugin $plugin */
		$plugin = $this->plugin(UndeletePlugin::class);
		return $plugin->actionUndelete(
			$comment,
			$this->buildLink('profile-posts/comments/undelete', $comment),
			$this->buildLink('profile-posts/comments', $comment),
			\XF::phrase('profile_post_comment_by_x', ['username' => $comment->username]),
			'message_state'
		);
	}

	public function actionCommentsApprove(ParameterBag $params)
	{
		$this->assertValidCsrfToken($this->filter('t', 'str'));

		$comment = $this->assertViewableComment($params->profile_post_comment_id);
		if (!$comment->canApproveUnapprove($error))
		{
			return $this->noPermission($error);
		}

		/** @var ApproverService $approver */
		$approver = \XF::service(ApproverService::class, $comment);
		$approver->approve();

		return $this->redirect($this->buildLink('profile-posts/comments', $comment));
	}

	public function actionCommentsUnapprove(ParameterBag $params)
	{
		$this->assertValidCsrfToken($this->filter('t', 'str'));

		$comment = $this->assertViewableComment($params->profile_post_comment_id);
		if (!$comment->canApproveUnapprove($error))
		{
			return $this->noPermission($error);
		}

		$comment->message_state = 'moderated';
		$comment->save();

		return $this->redirect($this->buildLink('profile-posts/comments', $comment));
	}

	public function actionCommentsWarn(ParameterBag $params)
	{
		$comment = $this->assertViewableComment($params->profile_post_comment_id);
		if (!$comment->canWarn($error))
		{
			return $this->noPermission($error);
		}

		$breadcrumbs = $this->getProfilePostBreadcrumbs($comment->ProfilePost);

		/** @var WarnPlugin $warnPlugin */
		$warnPlugin = $this->plugin(WarnPlugin::class);
		return $warnPlugin->actionWarn(
			'profile_post_comment',
			$comment,
			$this->buildLink('profile-posts/comments/warn', $comment),
			$breadcrumbs
		);
	}

	public function actionCommentsIp(ParameterBag $params)
	{
		$comment = $this->assertViewableComment($params->profile_post_comment_id);
		$breadcrumbs = $this->getProfilePostBreadcrumbs($comment->ProfilePost);

		/** @var IpPlugin $ipPlugin */
		$ipPlugin = $this->plugin(IpPlugin::class);
		return $ipPlugin->actionIp($comment, $breadcrumbs);
	}

	public function actionCommentsReport(ParameterBag $params)
	{
		$comment = $this->assertViewableComment($params->profile_post_comment_id);
		if (!$comment->canReport($error))
		{
			return $this->noPermission($error);
		}

		/** @var ReportPlugin $reportPlugin */
		$reportPlugin = $this->plugin(ReportPlugin::class);
		return $reportPlugin->actionReport(
			'profile_post_comment',
			$comment,
			$this->buildLink('profile-posts/comments/report', $comment),
			$this->buildLink('profile-posts/comments', $comment)
		);
	}

	public function actionCommentsReact(ParameterBag $params)
	{
		$comment = $this->assertViewableComment($params->profile_post_comment_id);

		/** @var ReactionPlugin $reactionPlugin */
		$reactionPlugin = $this->plugin(ReactionPlugin::class);
		return $reactionPlugin->actionReactSimple($comment, 'profile-posts/comments');
	}

	public function actionCommentsReactions(ParameterBag $params)
	{
		$comment = $this->assertViewableComment($params->profile_post_comment_id);

		$breadcrumbs = $this->getProfilePostBreadcrumbs($comment->ProfilePost);

		/** @var ReactionPlugin $reactionPlugin */
		$reactionPlugin = $this->plugin(ReactionPlugin::class);
		return $reactionPlugin->actionReactions(
			$comment,
			'profile-posts/comments/reactions',
			null,
			$breadcrumbs
		);
	}

	/**
	 * @param ProfilePost $profilePost
	 *
	 * @return EditorService
	 */
	protected function setupEdit(Entity $profilePost)
	{
		$message = $this->plugin(EditorPlugin::class)->fromInput('message');

		/** @var EditorService $editor */
		$editor = $this->service(EditorService::class, $profilePost);
		$editor->setMessage($message);

		if ($profilePost->canUploadAndManageAttachments())
		{
			$editor->setAttachmentHash($this->filter('attachment_hash', 'str'));
		}

		if ($this->filter('author_alert', 'bool') && $profilePost->canSendModeratorActionAlert())
		{
			$editor->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
		}

		return $editor;
	}

	protected function finalizeEdit(EditorService $editor)
	{
	}

	/**
	 * @param ProfilePostComment $comment
	 *
	 * @return \XF\Service\ProfilePostComment\EditorService
	 */
	protected function setupCommentEdit(ProfilePostComment $comment)
	{
		$message = $this->plugin(EditorPlugin::class)->fromInput('message');

		/** @var \XF\Service\ProfilePostComment\EditorService $editor */
		$editor = $this->service(\XF\Service\ProfilePostComment\EditorService::class, $comment);
		$editor->setMessage($message);

		$profilePost = $comment->ProfilePost;

		if ($profilePost->canUploadAndManageAttachments())
		{
			$editor->setAttachmentHash($this->filter('attachment_hash', 'str'));
		}

		if ($this->filter('author_alert', 'bool') && $comment->canSendModeratorActionAlert())
		{
			$editor->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
		}

		return $editor;
	}

	protected function finalizeCommentEdit(\XF\Service\ProfilePostComment\EditorService $editor)
	{
	}

	protected function getProfilePostBreadcrumbs(ProfilePost $profilePost)
	{
		$breadcrumbs = [
			[
				'href' => $this->buildLink('members', $profilePost->ProfileUser),
				'value' => $profilePost->ProfileUser->username,
			],
		];

		return $breadcrumbs;
	}

	/**
	 * @param $profilePostId
	 * @param array $extraWith
	 *
	 * @return ProfilePost
	 *
	 * @throws Exception
	 */
	protected function assertViewableProfilePost($profilePostId, array $extraWith = [])
	{
		$extraWith[] = 'User';
		$extraWith[] = 'ProfileUser';
		$extraWith[] = 'ProfileUser.Privacy';
		$extraWith = array_unique($extraWith);

		/** @var ProfilePost $profilePost */
		$profilePost = $this->em()->find(ProfilePost::class, $profilePostId, $extraWith);
		if (!$profilePost)
		{
			throw $this->exception($this->notFound(\XF::phrase('requested_profile_post_not_found')));
		}
		if (!$profilePost->canView($error))
		{
			throw $this->exception($this->noPermission($error));
		}

		return $profilePost;
	}

	/**
	 * @param $commentId
	 * @param array $extraWith
	 *
	 * @return ProfilePostComment
	 *
	 * @throws Exception
	 */
	protected function assertViewableComment($commentId, array $extraWith = [])
	{
		$extraWith[] = 'User';
		$extraWith[] = 'ProfilePost.ProfileUser';
		$extraWith[] = 'ProfilePost.ProfileUser.Privacy';
		$extraWith = array_unique($extraWith);

		/** @var ProfilePostComment $comment */
		$comment = $this->em()->find(ProfilePostComment::class, $commentId, $extraWith);
		if (!$comment)
		{
			throw $this->exception($this->notFound(\XF::phrase('requested_comment_not_found')));
		}
		if (!$comment->canView($error))
		{
			throw $this->exception($this->noPermission($error));
		}

		return $comment;
	}

	/**
	 * @return ProfilePostRepository
	 */
	protected function getProfilePostRepo()
	{
		return $this->repository(ProfilePostRepository::class);
	}

	public static function getActivityDetails(array $activities)
	{
		return \XF::phrase('viewing_members');
	}

	public static function getResolvableActions(): array
	{
		return ['index', 'show'];
	}

	public static function resolveToEmbeddableContent(ParameterBag $params, RouteMatch $routeMatch): ?Entity
	{
		$content = null;

		if ($params->profile_post_id)
		{
			/** @var ProfilePost $content */
			$content = \XF::em()->find(ProfilePost::class, $params->profile_post_id);
		}

		if (!$content || !$content->canView())
		{
			$content = null;
		}

		return $content;
	}
}
