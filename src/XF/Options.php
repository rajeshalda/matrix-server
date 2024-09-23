<?php

namespace XF;

/**
 * @property array|null $acpSearchExclude Quick search content types
 * @property int|null $activityLogLength Activity log length
 * @property array{enabled: bool, last_activity_min_days: int, email_frequency_days: int, last_activity_max_days: int}|null $activitySummaryEmail Enable activity summary email
 * @property positive-int|null $activitySummaryEmailBatchLimit Activity summary email batch limit
 * @property int|null $addBanUserGroup Add user group on ban
 * @property bool|null $adminRequireTfa Require two-step verification to access the admin control panel
 * @property string|null $adsDisallowedTemplates Prevent ads showing in these templates
 * @property string|null $akismetKey Akismet API key
 * @property positive-int|null $alertExpiryDays Days to retain viewed alerts
 * @property positive-int|null $alertsPerPage Alerts per-page
 * @property positive-int|null $alertsPopupExpiryDays Days to retain viewed alerts in popup
 * @property bool|null $allowExternalEmbed Allow external embedding of content
 * @property bool|null $allowGuestRte Allow guests to use the rich text editor
 * @property array{enabled: string, size: string}|null $allowVideoUploads Allow video/audio uploads with maximum file size
 * @property string|null $allowedCodeLanguages Allowed code BB code block languages
 * @property array{enabled: string, days: string}|null $approveSharedBannedRejectedIp Manually approve registration if user shares IP used by a banned or rejected user in last:
 * @property string|null $attachmentExtensions Allowed attachment file extensions
 * @property array{width: string, height: string}|null $attachmentMaxDimensions Maximum attachment image dimensions
 * @property non-negative-int|null $attachmentMaxFileSize Maximum attachment file size
 * @property non-negative-int|null $attachmentMaxPerMessage Maximum attachments per message
 * @property positive-int|null $attachmentThumbnailDimensions Attachment thumbnail dimensions
 * @property array{embedType: string, linkBbCode: string}|null $autoEmbedMedia Auto-embed media links
 * @property bool|null $boardActive Board is active
 * @property string|null $boardDescription Board meta description
 * @property string|null $boardInactiveMessage Inactive board message
 * @property string|null $boardShortTitle Board short title
 * @property string|null $boardTitle Board title
 * @property string|null $boardUrl Board URL
 * @property bool|null $boardUrlCanonical Enable board URL canonicalization
 * @property string|null $bounceEmailAddress Bounced email address
 * @property string|null $captcha Enable CAPTCHA for guests
 * @property bool|null $categoryOwnPage Create pages for categories
 * @property string|null $censorCharacter Censor character
 * @property array|null $censorWords Words to censor
 * @property non-negative-int|null $changeLogLength Change log length
 * @property array{configured: string, enabled: string, installation_id: string, last_sent: string}|null $collectServerStats Send anonymous usage statistics
 * @property string|null $contactEmailAddress Contact email address
 * @property bool|null $contactEmailSenderHeader Sender info in from header on contact emails
 * @property array{type: string, custom: bool, overlay: bool}|null $contactUrl Contact URL
 * @property positive-int|null $conversationPopupExpiryHours Hours to retain read direct messages in popup
 * @property bool|null $convertMarkdownToBbCode Convert Markdown-style content to BB code
 * @property array{type: string}|null $cookieConsent Cookie consent
 * @property int|null $cookieConsentLogLength Cookie consent log length
 * @property positive-int|null $currentVersionId Current version ID
 * @property string|null $defaultEmailAddress Default email address
 * @property non-negative-int|null $defaultEmailStyleId Default email style
 * @property positive-int|null $defaultLanguageId Default language
 * @property positive-int|null $defaultStyleId Default style
 * @property string|null $disallowedCustomTitles Disallowed custom titles
 * @property string|null $discourageBlankChance Blank page chance
 * @property array{min: string, max: string}|null $discourageDelay Loading delay
 * @property positive-int|null $discourageFloodMultiplier Flood time multiplier
 * @property non-negative-int|null $discourageRedirectChance Redirection chance
 * @property string|null $discourageRedirectUrl Redirection page URL
 * @property non-negative-int|null $discourageSearchChance Search disabled chance
 * @property bool|null $discussionPreview Enable discussion preview
 * @property non-negative-int|null $discussionRssContentLength Discussion content RSS snippet length
 * @property positive-int|null $discussionsPerPage Discussions per page
 * @property string|null $displayVisitorCount Display visitor count
 * @property bool|null $dynamicAvatarEnable Enable dynamic default avatars
 * @property array{enabled: string, length: string}|null $editHistory Enable edit history tracking and prune after:
 * @property array{enabled: string, delay: string}|null $editLogDisplay Enable edit log display after:
 * @property array{xfList: array{cmd: string, icon: string, buttons: array{0: string, 1: string, 2: string, 3: string}}}|null $editorDropdownConfig Editor dropdown config
 * @property array{toolbarButtons: array{moreText: array{buttons: array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string, 9: string}, buttonsVisible: string, align: string}, moreParagraph: array{buttons: array{0: string, 1: string, 2: string}, buttonsVisible: string, align: string}, moreRich: array{buttons: array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string, 9: string}, buttonsVisible: string, align: string}, moreMisc: array{buttons: array{0: string, 1: string, 2: string, 3: string}, buttonsVisible: string, align: string}}, toolbarButtonsMD: array{moreText: array{buttons: array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string}, buttonsVisible: string, align: string}, moreParagraph: array{buttons: array{0: string, 1: string, 2: string}, buttonsVisible: string, align: string}, moreRich: array{buttons: array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string, 9: string}, buttonsVisible: string, align: string}, moreMisc: array{buttons: array{0: string, 1: string, 2: string, 3: string, 4: string}, buttonsVisible: string, align: string}}, toolbarButtonsSM: array{moreText: array{buttons: array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string}, buttonsVisible: string, align: string}, moreParagraph: array{buttons: array{0: string, 1: string, 2: string}, buttonsVisible: string, align: string}, moreRich: array{buttons: array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string, 9: string}, buttonsVisible: string, align: string}, moreMisc: array{buttons: array{0: string, 1: string, 2: string, 3: string, 4: string}, buttonsVisible: string, align: string}}, toolbarButtonsXS: array{moreText: array{buttons: array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string, 9: string, 10: string, 11: string}, buttonsVisible: string, align: string}, moreParagraph: array{buttons: array, buttonsVisible: string, align: string}, moreRich: array{buttons: array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string, 9: string}, buttonsVisible: string, align: string}, moreMisc: array{buttons: array{0: string, 1: string, 2: string, 3: string, 4: string}, buttonsVisible: string, align: string}}}|null $editorToolbarConfig Editor toolbar config
 * @property array{enabled: bool, type: bool, host: bool, port: bool, username: bool, password: bool, encryption: bool, oauth: bool}|null $emailBounceHandler Automated bounced email handler
 * @property bool|null $emailConversationIncludeMessage Include full message text in direct message notification emails.
 * @property array{enabled: bool, verified: bool, failed: bool, domain: bool, privateKey: bool}|null $emailDkim DKIM email authentication
 * @property array{enabled: string, email: string}|null $emailFileCheckWarning Send file health check warning emails:
 * @property string|null $emailSenderName Default email sender name
 * @property bool|null $emailShare Enable email share button
 * @property array{bounce_total: string, unique_days: string, days_between: string}|null $emailSoftBounceThreshold Soft bounce trigger threshold
 * @property array{emailTransport: string}|null $emailTransport Email transport method
 * @property array{enabled: bool, type: bool, host: bool, port: bool, username: bool, password: bool, encryption: bool, oauth: bool}|null $emailUnsubscribeHandler Automated unsubscribe email handler
 * @property bool|null $emailWatchedThreadIncludeMessage Include full message text in watched thread/forum notification emails.
 * @property bool|null $embedCodeShare Enable embed code sharing
 * @property bool|null $embedTemplateNames Embed template names in HTML
 * @property array{source: string, path: string}|null $emojiSource Emoji source
 * @property string|null $emojiStyle Emoji style
 * @property bool|null $enableMemberList Enable 'Registered members' list
 * @property bool|null $enableNewsFeed Enable news feed
 * @property bool|null $enableNotices Enable notices system
 * @property bool|null $enablePush Enable push notifications
 * @property bool|null $enableSearch Enable search engine
 * @property bool|null $enableTagging Enable content tagging
 * @property bool|null $enableTrophies Enable trophies
 * @property bool|null $enableVerp Enable variable email address values for automated email handling
 * @property array|null $extraCaptchaKeys Extra CAPTCHA keys
 * @property string|null $extraFaIcons Additional icons to include in sprites
 * @property bool|null $facebookLike Enable Facebook share button
 * @property non-negative-int|null $floodCheckLength Minimum time between messages
 * @property non-negative-int|null $floodCheckLengthDiscussion Minimum time between discussions
 * @property string|null $forumsDefaultPage Forums default page
 * @property string|null $geoLocationUrl Location information URL
 * @property array{enabled: bool, api_key: bool, rating: bool}|null $giphy Enable GIPHY support
 * @property bool|null $googleAnalyticsAnonymize Anonymize IP addresses for Google Analytics
 * @property string|null $googleAnalyticsWebPropertyId Google Analytics web property ID
 * @property bool|null $gravatarEnable Enable Gravatar support
 * @property bool|null $guestShowSignatures Show signatures to guests
 * @property string|null $guestTimeZone Guests' time zone
 * @property string|null $homePageUrl Home page URL
 * @property non-negative-int|null $iconSpriteLastUpdate Icon sprite last update
 * @property non-negative-int|null $imageCacheRefresh Image cache refresh
 * @property non-negative-int|null $imageCacheTTL Image cache lifetime
 * @property string|null $imageLibrary Default image processor
 * @property array{images: bool, links: bool}|null $imageLinkProxy Image and link proxy
 * @property string|null $imageLinkProxyKey Image and link proxy secret key
 * @property non-negative-int|null $imageLinkProxyLogLength Image and link proxy log length
 * @property array{enabled: string, length: string}|null $imageLinkProxyReferrer Log image and link proxy referrers for x days:
 * @property string|null $imageOptimization Image optimization
 * @property array{bypassType: string, bypassDomains: string}|null $imageProxyBypass Image proxy bypass
 * @property int|null $imageProxyMaxSize Image cache max size
 * @property bool|null $includeCaptchaPrivacyPolicy Include CAPTCHA privacy policy
 * @property string|null $includeEmojiInTitles Include emoji in content title in URLs
 * @property bool|null $includeTitleInUrls Include content title in URLs
 * @property array{enabled: bool, key: bool}|null $indexNow Enable support for IndexNow
 * @property string|null $indexRoute Index page route
 * @property string|null $ipInfoUrl IP information URL
 * @property array{enabled: string, delay: string}|null $ipLogCleanUp Delete IP usage data after:
 * @property string|null $jobRunTrigger Job run trigger
 * @property non-negative-int|null $jsLastUpdate JavaScript last update
 * @property non-negative-int|null $lastPageLinks Maximum last page links
 * @property bool|null $lightBoxUniversal Lightbox displays all attached thumbnails on page
 * @property bool|null $linkShare Enable link share button
 * @property bool|null $linkedinShare Enable LinkedIn share button
 * @property string|null $loginLimit Login limit method
 * @property bool|null $logoLink Link logo to home page URL
 * @property bool|null $lostPasswordCaptcha Use CAPTCHA for lost password form
 * @property non-negative-int|null $lostPasswordTimeLimit Minimum time between lost password requests
 * @property non-negative-int|null $maxContentSpamMessages Maximum messages to check for spam
 * @property non-negative-int|null $maxContentTags Max content tags
 * @property non-negative-int|null $maxContentTagsPerUser Max content tags per user
 * @property positive-int|null $maximumSearchResults Maximum number of search/find new results
 * @property positive-int|null $membersPerPage Members to list per-page
 * @property non-negative-int|null $messageMaxImages Maximum images per message
 * @property non-negative-int|null $messageMaxLength Maximum message length
 * @property non-negative-int|null $messageMaxMedia Maximum media embeds per message
 * @property positive-int|null $messagesPerPage Messages per page
 * @property int|null $moderatorLogLength Moderator log length
 * @property bool|null $multiQuote Enable multi-quote
 * @property positive-int|null $newsFeedMaxItems News feed items to fetch with each request
 * @property positive-int|null $newsFeedMessageSnippetLength News feed message snippet maximum length
 * @property non-negative-int|null $oEmbedCacheRefresh oEmbed media cache refresh
 * @property non-negative-int|null $oEmbedCacheTTL oEmbed media cache lifetime
 * @property non-negative-int|null $oEmbedLogLength oEmbed media log length
 * @property array{enabled: string, length: string}|null $oEmbedRequestReferrer Log oEmbed request referrers for x days:
 * @property positive-int|null $onlineStatusTimeout Online status timeout
 * @property bool|null $pinterestShare Enable Pinterest share button
 * @property non-negative-int|null $pollMaximumResponses Maximum number of poll choices
 * @property array{enabled: bool, userGroups: array{0: int}, permissionCombinationId: bool}|null $preRegAction Writing before registering setup
 * @property bool|null $preventDiscouragedRegistration Prevent discouraged IP addresses from registering
 * @property string|null $privacyPolicyForceWhitelist Force privacy policy agreement route whitelist
 * @property non-negative-int|null $privacyPolicyLastUpdate Privacy policy last update
 * @property array{type: string, custom: bool}|null $privacyPolicyUrl Privacy policy URL
 * @property non-negative-int|null $profilePostMaxLength Maximum profile message length
 * @property array{publicKey: string, privateKey: string}|null $pushKeysVAPID Push VAPID keys
 * @property non-negative-int|null $readMarkingDataLifetime Read marking data lifetime
 * @property bool|null $redditShare Enable Reddit share button
 * @property array{check: string, action: string, projectHoneyPotKey: string}|null $registrationCheckDnsBl DNS Blacklist &amp; Project Honey Pot
 * @property array{visible: string, activity_visible: string, content_show_signature: string, show_dob_date: string, show_dob_year: string, receive_admin_email: string, email_on_conversation: string, push_on_conversation: string, creation_watch_state: string, interaction_watch_state: string, allow_view_profile: string, allow_post_profile: string, allow_receive_news_feed: string, allow_send_personal_conversation: string, allow_view_identities: string}|null $registrationDefaults Default registration values
 * @property array{enabled: string, emailConfirmation: string, moderation: bool, requireDob: string, minimumAge: string, requireLocation: bool, requireEmailChoice: bool}|null $registrationSetup Registration setup
 * @property non-negative-int|null $registrationTimer Registration timer
 * @property array{messageEnabled: bool, messageParticipants: bool, messageTitle: bool, messageBody: bool, messageOpenInvite: bool, messageLocked: bool, messageDelete: bool, emailEnabled: bool, emailFromName: bool, emailFromEmail: bool, emailTitle: bool, emailFormat: bool, emailBody: bool}|null $registrationWelcome New user welcome
 * @property non-negative-int|null $reportIntoForumId Send reports into forum
 * @property bool|null $romanizeUrls Romanize titles in URLs
 * @property string|null $rootBreadcrumb Root breadcrumb
 * @property array{enabled: string, lifetime: string, saveFrequency: string}|null $saveDrafts Save drafts as messages are being composed
 * @property positive-int|null $searchMinWordLength Search minimum word length
 * @property positive-int|null $searchResultsPerPage Search results per page
 * @property array{enabled: bool}|null $searchSuggestions Enable search suggestions
 * @property bool|null $selectQuotable Enable select-to-quote
 * @property bool|null $sendUnsubscribeConfirmation Send email unsubscribe confirmation
 * @property positive-int|null $sharedIpsCheckLimit Shared IPs check limit
 * @property bool|null $shortcodeToEmoji Convert short code to emoji / smilies
 * @property bool|null $showEmojiInSmilieMenu Show emoji in smilie menu
 * @property bool|null $showMessageOnlineStatus Show online status indicator
 * @property bool|null $sitemapAutoRebuild Automatically build sitemap
 * @property array{enabled: string, urls: string}|null $sitemapAutoSubmit Automatically submit sitemap to search engines
 * @property array|null $sitemapExclude Included sitemap content
 * @property string|null $sitemapExtraUrls Extra sitemap URLs
 * @property array{ban_user: string, delete_messages: string, delete_conversations: string, action_threads: string, check_ips: string}|null $spamDefaultOptions Spam cleaner default options
 * @property string|null $spamMessageAction Spam cleaner message action
 * @property array{phrases: string, action: string}|null $spamPhrases Spam phrases
 * @property array{action: string, node_id: string}|null $spamThreadAction Spam cleaner thread action
 * @property array{message_count: string, register_date: string, reaction_score: string}|null $spamUserCriteria Spam cleaner user criteria
 * @property array{enabled: string, denyThreshold: string, moderateThreshold: string, frequencyCutOff: string, lastSeenCutOff: string, hashEmail: bool, submitRejections: bool, apiKey: bool}|null $stopForumSpam Stop Forum Spam
 * @property array{enabled: string, count: string}|null $tagCloud Enable tag cloud with up to X tags:
 * @property positive-int|null $tagCloudMinUses Minimum tag cloud uses
 * @property array{min: string, max: string}|null $tagLength Tag length limit
 * @property array{disallowedWords: string, matchRegex: string}|null $tagValidation Tag validation
 * @property non-negative-int|null $templateHistoryLength Template history length
 * @property non-negative-int|null $termsLastUpdate Terms and rules last update
 * @property string|null $test Test
 * @property string|null $tosForceWhitelist Force terms and rules agreement route whitelist
 * @property array{type: string, custom: bool}|null $tosUrl Terms and rules URL
 * @property int|null $trendingContentHalfLife Trending content half life
 * @property array{view_count: int, reply_count: int, reaction_count: int, reaction_score: int, vote_count: int, vote_score: int}|null $trendingContentWeights Trending content weights
 * @property bool|null $tumblrShare Enable Tumblr share button
 * @property array{enabled: string, via: string, related: string}|null $tweet Enable X (Twitter) share button
 * @property string|null $unsubscribeEmailAddress Unsubscribe email address
 * @property array{http: bool, email: bool}|null $unsubscribeEmailHandling Unsubscribe email handling
 * @property bool|null $upgradeCheckStableOnly Only check for stable upgrades
 * @property bool|null $urlToEmbedResolver Convert internal URL to embed
 * @property array{enabled: string, format: string}|null $urlToPageTitle Convert URLs to page titles
 * @property bool|null $urlToRichPreview Unfurl URL to a rich preview automatically
 * @property bool|null $useFriendlyUrls Use full friendly URLs
 * @property array{showStaff: string, displayMultiple: string, hideUserTitle: bool, showStaffAndOther: bool}|null $userBanners User banners
 * @property bool|null $userMentionKeepAt Keep @ character with user mentions
 * @property string|null $userTitleLadderField User title ladder field
 * @property non-negative-int|null $usernameChangeRecentLimit Username change recent limit
 * @property bool|null $usernameChangeRequireReason Require reason for username changes
 * @property non-negative-int|null $usernameChangeTimeLimit Minimum time between username changes
 * @property array{min: string, max: string}|null $usernameLength Username length limit
 * @property int|null $usernameReuseTimeLimit Username reuse time limit
 * @property array{disallowedNames: string, matchRegex: string}|null $usernameValidation Username validation
 * @property array{guest: string, robot: bool}|null $viewCounts Content view counts
 * @property array{enabled: string, days: string}|null $watchAlertActiveOnly Only send watched content alerts/emails to users active in last:
 * @property bool|null $webShare Enable web share button
 * @property bool|null $whatsAppShare Enable WhatsApp share buttonclass Options extends \ArrayObject
 */
class Options extends \ArrayObject
{
	public function __construct(array $array = [])
	{
		parent::__construct($array, \ArrayObject::ARRAY_AS_PROPS);
	}

	/**
	 * @param mixed $key
	 *
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function &offsetGet($key)
	{
		try
		{
			$value = parent::offsetGet($key);
		}
		catch (\ErrorException $e)
		{
			if (\XF::$debugMode)
			{
				throw $e;
			}

			$value = null;
			\XF::logException($e);
		}

		return $value;
	}
}
