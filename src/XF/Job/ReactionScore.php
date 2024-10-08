<?php

namespace XF\Job;

use XF\Repository\ReactionRepository;

class ReactionScore extends AbstractRebuildJob
{
	protected function getNextIds($start, $batch)
	{
		$db = $this->app->db();

		return $db->fetchAllColumn($db->limit(
			"
				SELECT user_id
				FROM xf_user
				WHERE user_id > ?
				ORDER BY user_id
			",
			$batch
		), $start);
	}

	protected function rebuildById($id)
	{
		$db = $this->app->db();

		/** @var ReactionRepository $reactionRepo */
		$reactionRepo = $this->app->repository(ReactionRepository::class);

		$db->beginTransaction();
		$count = $reactionRepo->getUserReactionScore($id);

		$this->app->db()->update('xf_user', ['reaction_score' => $count], 'user_id = ?', $id);
		$db->commit();
	}

	protected function getStatusType()
	{
		return \XF::phrase('reaction_score');
	}
}
