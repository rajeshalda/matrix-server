<?php

namespace XF\Service\Poll;

use XF\App;
use XF\Entity\Poll;
use XF\Repository\PollRepository;
use XF\Service\AbstractService;
use XF\Service\ValidateAndSavableTrait;

use function count;

class VoterService extends AbstractService
{
	use ValidateAndSavableTrait;

	/** @var Poll */
	protected $poll;

	protected $votes = [];

	public function __construct(App $app, Poll $poll, array $votes = [])
	{
		parent::__construct($app);
		$this->poll = $poll;
		$this->setVotes($votes);
	}

	public function getPoll()
	{
		return $this->poll;
	}

	public function setVotes(array $votes)
	{
		$responses = $this->poll->responses;
		foreach ($votes AS $k => $responseId)
		{
			if (!isset($responses[$responseId]))
			{
				unset($votes[$k]);
			}
		}

		$this->votes = array_values($votes);
	}

	protected function _validate()
	{
		$totalVotes = count($this->votes);
		$maxVotes = $this->poll->max_votes;
		$errors = [];

		if ($maxVotes && $totalVotes > $maxVotes)
		{
			$errors[] = \XF::phrase('you_may_select_up_to_x_choices', ['max' => $maxVotes]);
		}
		else if (!$totalVotes)
		{
			$errors[] = \XF::phrase('please_vote_for_at_least_one_option');
		}

		return $errors;
	}

	protected function _save()
	{
		return $this->repository(PollRepository::class)->voteOnPoll($this->poll, $this->votes);
	}
}
