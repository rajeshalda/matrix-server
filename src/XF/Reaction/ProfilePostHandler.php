<?php

namespace XF\Reaction;

use XF\Mvc\Entity\Entity;

class ProfilePostHandler extends AbstractHandler
{
	public function reactionsCounted(Entity $entity)
	{
		return ($entity->message_state == 'visible');
	}

	public function getEntityWith()
	{
		return ['ProfileUser', 'ProfileUser.Privacy'];
	}
}
