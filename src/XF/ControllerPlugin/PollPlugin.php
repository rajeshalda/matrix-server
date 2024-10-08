<?php

namespace XF\ControllerPlugin;

use XF\Helper\Poll;
use XF\Mvc\Entity\Entity;
use XF\Poll\AbstractHandler;
use XF\Repository\PollRepository;
use XF\Service\Poll\CreatorService;
use XF\Service\Poll\DeleterService;
use XF\Service\Poll\EditorService;
use XF\Service\Poll\ResetterService;
use XF\Service\Poll\VoterService;

class PollPlugin extends AbstractPlugin
{
	public function actionCreate($contentType, Entity $content, array $breadcrumbs = [])
	{
		/** @var PollRepository $pollRepo */
		$pollRepo = $this->repository(PollRepository::class);
		$handler = $pollRepo->getPollHandler($contentType);

		if (!$handler->canCreate($content, $error))
		{
			return $this->noPermission();
		}

		if ($this->isPost())
		{
			$creator = $this->setupPollCreate($contentType, $content);

			if (!$creator->validate($errors))
			{
				return $this->error($errors);
			}

			$creator->save();

			return $this->redirect($this->getDynamicRedirect());
		}
		else
		{
			$redirect = $this->getDynamicRedirect(
				$handler->getPollLink('content', $content)
			);

			$viewParams = [
				'createFormUrl' => $handler->getPollLink('create', $content),

				'breadcrumbs' => $breadcrumbs,
				'redirect' => $redirect,
			];
			return $this->view('XF:Poll\Create', 'poll_create', $viewParams);
		}
	}

	/**
	 * @param $contentType
	 * @param Entity $content
	 *
	 * @return CreatorService
	 */
	public function setupPollCreate($contentType, Entity $content)
	{
		/** @var Poll $pollHelper */
		$pollHelper = $this->helper(Poll::class);

		/** @var CreatorService $creator */
		$creator = $this->service(CreatorService::class, $contentType, $content);

		return $pollHelper->configureCreatorFromInput(
			$creator,
			$pollHelper->getPollInput($this->request)
		);
	}

	public function actionEdit($poll, array $breadcrumbs = [])
	{
		if (!($poll instanceof \XF\Entity\Poll))
		{
			return $this->notFound();
		}

		/** @var AbstractHandler $handler */
		$handler = $poll->Handler;
		$contentType = $poll->content_type;
		$content = $poll->Content;

		if (!$poll->canEdit($error))
		{
			return $this->noPermission($error);
		}

		if ($this->isPost())
		{
			$editor = $this->setupPollEdit($poll, $contentType, $content, $handler);
			if (!$editor->validate($errors))
			{
				return $this->error($errors);
			}

			$editor->save();

			return $this->redirect($this->getDynamicRedirect());
		}
		else
		{
			$redirect = $this->getDynamicRedirect(
				$handler->getPollLink('content', $content)
			);

			$viewParams = [
				'poll' => $poll,
				'breadcrumbs' => $breadcrumbs,
				'redirect' => $redirect,
			];
			return $this->view('XF:Poll\Edit', 'poll_edit', $viewParams);
		}
	}

	/**
	 * @param \XF\Entity\Poll $poll
	 * @param string $contentType
	 * @param Entity $content
	 * @param AbstractHandler $handler
	 *
	 * @return EditorService
	 */
	protected function setupPollEdit(\XF\Entity\Poll $poll, $contentType, Entity $content, AbstractHandler $handler)
	{
		$pollInput = $this->getPollInput();

		/** @var EditorService $editor */
		$editor = $this->service(EditorService::class, $poll);

		if ($poll->canEditDetails())
		{
			$editor->setQuestion($pollInput['question']);
			$editor->updateExistingResponses($pollInput['existing_responses']);
		}
		$editor->addResponses($pollInput['new_responses']);

		if ($poll->canEditMaxVotes())
		{
			$editor->setMaxVotes($pollInput['max_votes_type'], $pollInput['max_votes_value']);
		}

		if ($poll->canChangePollVisibility())
		{
			$editor->setPublicVotes($pollInput['public_votes']);
		}

		if ($pollInput['close'])
		{
			$editor->setCloseDateRelative($pollInput['close_length'], $pollInput['close_units']);
		}
		else if (!$pollInput['remove_close'])
		{
			$editor->removeCloseDate();
		}

		$editor->setOptions([
			'change_vote' => $pollInput['change_vote'],
			'view_results_unvoted' => $pollInput['view_results_unvoted'],
		]);

		return $editor;
	}

	public function actionDelete($poll, array $breadcrumbs = [])
	{
		if (!($poll instanceof \XF\Entity\Poll))
		{
			return $this->notFound();
		}

		if (!$poll->canDelete($error))
		{
			return $this->noPermission($error);
		}

		if ($this->isPost())
		{
			$action = $this->filter('poll_action', 'str');

			if ($action == 'remove')
			{
				$this->service(DeleterService::class, $poll)->delete();
			}
			else if ($action == 'reset')
			{
				$this->service(ResetterService::class, $poll)->reset();
			}

			return $this->redirect($this->getDynamicRedirect());
		}
		else
		{
			$viewParams = [
				'poll' => $poll,
				'breadcrumbs' => $breadcrumbs,
			];
			return $this->view('XF:Poll\Delete', 'poll_delete', $viewParams);
		}
	}

	public function actionVote($poll, array $breadcrumbs = [])
	{
		if (!($poll instanceof \XF\Entity\Poll))
		{
			return $this->notFound();
		}

		if (!$poll->canVote($error))
		{
			return $this->noPermission($error);
		}

		if ($this->isPost())
		{
			$voteResponseIds = $this->filter('responses', 'array-uint');

			/** @var VoterService $voter */
			$voter = $this->service(VoterService::class, $poll, $voteResponseIds);
			if (!$voter->validate($errors))
			{
				return $this->error($errors);
			}

			$voter->save();

			$viewParams = [
				'poll' => $poll,
				'breadcrumbs' => $breadcrumbs,
				'simpleDisplay' => $this->filter('simple_display', 'bool'),
			];
			return $this->view('XF:Poll\Block', 'poll_block', $viewParams);
		}
		else
		{
			$viewParams = [
				'poll' => $poll,
				'breadcrumbs' => $breadcrumbs,
				'simpleDisplay' => $this->filter('simple_display', 'bool'),
			];
			return $this->view('XF:Poll\Vote', 'poll_vote', $viewParams);
		}
	}

	public function actionResults($poll, array $breadcrumbs = [])
	{
		if (!($poll instanceof \XF\Entity\Poll))
		{
			return $this->notFound();
		}

		if (!$poll->canViewResults($error))
		{
			return $this->noPermission($error);
		}

		$responseId = $this->filter('response', 'uint');

		if ($responseId)
		{
			if (!isset($poll->Responses[$responseId]))
			{
				return $this->notFound();
			}

			if (!$poll->public_votes)
			{
				return $this->noPermission();
			}

			$viewParams = [
				'poll' => $poll,
				'response' => $poll->Responses[$responseId],
				'breadcrumbs' => $breadcrumbs,
			];
			return $this->view('XF:Poll\Voters', 'poll_voters', $viewParams);
		}
		else
		{
			$viewParams = [
				'poll' => $poll,
				'breadcrumbs' => $breadcrumbs,
			];
			return $this->view('XF:Poll\Results', 'poll_results', $viewParams);
		}
	}

	public function getPollInput()
	{
		return $this->helper(Poll::class)->getPollInput($this->request);
	}
}
