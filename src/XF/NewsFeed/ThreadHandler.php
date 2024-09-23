<?php

namespace XF\NewsFeed;

use XF\Repository\AttachmentRepository;

class ThreadHandler extends AbstractHandler
{
	public function getEntityWith()
	{
		$visitor = \XF::visitor();

		return ['User', 'FirstPost', 'Forum', 'Forum.Node.Permissions|' . $visitor->permission_combination_id];
	}

	protected function addAttachmentsToContent($content)
	{
		$firstPosts = [];
		foreach ($content AS $thread)
		{
			$firstPost = $thread->FirstPost;
			if ($firstPost)
			{
				$firstPosts[$firstPost->post_id] = $firstPost;
			}
		}

		/** @var AttachmentRepository $attachmentRepo */
		$attachmentRepo = \XF::repository(AttachmentRepository::class);
		$attachmentRepo->addAttachmentsToContent($firstPosts, 'post');

		return $content;
	}
}
