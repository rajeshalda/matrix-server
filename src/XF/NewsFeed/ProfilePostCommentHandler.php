<?php

namespace XF\NewsFeed;

class ProfilePostCommentHandler extends AbstractHandler
{
	public function getEntityWith()
	{
		return ['ProfilePost', 'ProfilePost.ProfileUser', 'ProfilePost.ProfileUser.Privacy'];
	}
}
