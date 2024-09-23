<?php

namespace XF\Service\Report;

use XF\App;
use XF\Entity\Report;
use XF\Entity\ReportComment;
use XF\Entity\User;
use XF\Repository\ReportRepository;
use XF\Repository\UserAlertRepository;
use XF\Service\AbstractService;

class NotifierService extends AbstractService
{
	/**
	 * @var Report
	 */
	protected $report;

	/**
	 * @var ReportComment
	 */
	protected $comment;

	protected $notifyMentioned = [];
	protected $notifyCreated = [];

	protected $usersAlerted = [];
	protected $usersEmailed = [];

	public function __construct(App $app, Report $report, ReportComment $comment)
	{
		parent::__construct($app);
		$this->report = $report;
		$this->comment = $comment;
	}

	public function getReport()
	{
		return $this->report;
	}

	public function getComment()
	{
		return $this->comment;
	}

	public function setNotifyMentioned(array $mentioned)
	{
		$this->notifyMentioned = array_unique($mentioned);
	}

	public function getNotifyMentioned()
	{
		return $this->notifyMentioned;
	}

	public function setNotifyCreated(array $userIds): void
	{
		$this->notifyCreated = array_unique($userIds);
	}

	public function getNotifyCreated()
	{
		return $this->notifyCreated;
	}

	public function notifyMentioned()
	{
		$notifiableUsers = $this->getUsersForMentionedNotification();

		$mentionUsers = $this->getNotifyMentioned();
		foreach ($mentionUsers AS $k => $userId)
		{
			if (isset($notifiableUsers[$userId]))
			{
				$user = $notifiableUsers[$userId];
				if (\XF::asVisitor($user, function () { return $this->report->canView(); }))
				{
					$this->sendMentionNotification($user);
				}
			}
			unset($mentionUsers[$k]);
		}
		$this->notifyMentioned = [];
	}

	protected function getUsersForMentionedNotification()
	{
		$userIds = $this->getNotifyMentioned();

		$users = $this->app->em()->findByIds(User::class, $userIds, ['Profile', 'Option']);
		if (!$users->count())
		{
			return [];
		}

		$users = $users->toArray();
		foreach ($users AS $k => $user)
		{
			if (!\XF::asVisitor($user, function () { return $this->report->canView(); }))
			{
				unset($users[$k]);
			}
		}

		return $users;
	}

	protected function sendMentionNotification(User $user)
	{
		$comment = $this->comment;

		if (empty($this->usersAlerted[$user->user_id]) && ($user->user_id != $comment->user_id))
		{
			/** @var UserAlertRepository $alertRepo */
			$alertRepo = $this->app->repository(UserAlertRepository::class);
			$alerted = $alertRepo->alert(
				$user,
				$comment->user_id,
				$comment->username,
				'report',
				$comment->report_id,
				'mention',
				[
					'comment' => $comment->toArray(),
				],
				['autoRead' => false]
			);
			if ($alerted)
			{
				$this->usersAlerted[$user->user_id] = true;
				return true;
			}
		}

		return false;
	}

	protected function getUsersForCreatedNotification(): array
	{
		/** @var ReportRepository $reportRepo */
		$reportRepo = $this->repository(ReportRepository::class);
		$moderators = $reportRepo->getModeratorsWhoCanHandleReport($this->report, true);

		return $moderators->toArray();
	}

	public function notifyCreate()
	{
		$moderatorsToEmail = $this->getUsersForCreatedNotification();
		$report = $this->report;
		$comment = $this->comment;

		foreach ($moderatorsToEmail AS $moderator)
		{
			$user = $moderator->User;

			if (empty($this->usersEmailed[$user->user_id]) && $moderator->notify_report)
			{
				$params = [
					'receiver' => $user,
					'reporter' => $comment->User,
					'comment' => $comment,
					'report' => $report,
					'message' => $report->getContentMessage(),
				];

				$mailer = $this->app->mailer();
				$mailer->newMail()
					->setToUser($user)
					->setTemplate('report_create', $params)
					->queue();

				$this->usersEmailed[$user->user_id] = true;
			}
		}
	}
}
