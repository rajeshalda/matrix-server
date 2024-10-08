<?php

namespace XF\BbCode\Helper;

use XF\Entity\BbCodeMediaSite;

class Tumblr
{
	public const URL_REGEX = '#(?P<subdomain>[A-Z0-9_-]+)\.tumblr\.com/(?P<mediatype>post)/(?P<mediaid>\d+)#si';
	public const URL_REGEX_ALT = '#www\.tumblr\.com/(?P<subdomain>[A-Z0-9_-]+)/(?P<mediaid>\d+)#si';

	public static function matchCallback($url, $matchedId, BbCodeMediaSite $site, $siteId)
	{
		if (preg_match(self::URL_REGEX, $url, $mediaInfo))
		{
			return $mediaInfo['subdomain'] . '.tumblr.com/' . $mediaInfo['mediatype'] . '/' . $mediaInfo['mediaid'];
		}

		if (preg_match(self::URL_REGEX_ALT, $url, $mediaInfo))
		{
			return $mediaInfo['subdomain'] . '.tumblr.com/post/' . $mediaInfo['mediaid'];
		}

		return false;
	}
}
