<?php

namespace XF\NewsFeed;

class ProfilePostHandler extends AbstractHandler
{
	public function getEntityWith()
	{
		return ['ProfileUser', 'ProfileUser.Privacy'];
	}
}
