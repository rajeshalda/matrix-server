<?php

namespace XF\Job;

use XF\Entity\ConversationRecipient;
use XF\Entity\User;
use XF\Language;
use XF\Service\Conversation\CreatorService;
use XF\Util\Url;

class UserMessage extends AbstractUserCriteriaJob
{
	protected $defaultData = [
		'message' => [],
	];

	/**
	 * @var User|null
	 */
	protected $author;

	protected function actionSetup()
	{
		$this->author = $this->app->em()->find(User::class, $this->data['message']['user_id']);
	}

	protected function executeAction(User $user)
	{
		$message = $this->data['message'];

		$language = $this->app->userLanguage($user);
		$title = $this->replacePhrases($message['message_title'], $language);
		$body = $this->replacePhrases($message['message_body'], $language);

		$tokens = $this->prepareTokens($user);
		$title = strtr($title, $tokens);
		$body = strtr($body, $tokens);

		/** @var CreatorService $creator */
		$creator = $this->app->service(CreatorService::class, $this->author);
		$creator->setIsAutomated();
		$creator->setOptions([
			'open_invite' => $message['open_invite'],
			'conversation_open' => !$message['conversation_locked'],
		]);
		$creator->setRecipientsTrusted($user);
		$creator->setContent($title, $body);
		if (!$creator->validate())
		{
			return;
		}

		$conversation = $creator->save();

		if ($message['delete_type'])
		{
			/** @var ConversationRecipient $recipient */
			$recipient = $conversation->Recipients[$this->author->user_id];
			$recipient->recipient_state = $message['delete_type'];
			$recipient->save(false);
		}
	}

	protected function getActionDescription()
	{
		$actionPhrase = \XF::phrase('messaging');
		$typePhrase = \XF::phrase('users');

		return sprintf('%s... %s', $actionPhrase, $typePhrase);
	}

	protected function wrapTransaction()
	{
		return false;
	}

	protected function replacePhrases($string, Language $language)
	{
		return $this->app->stringFormatter()->replacePhrasePlaceholders($string, $language);
	}

	protected function prepareTokens(User $user)
	{
		return [
			'{name}' => $user->username,
			'{email}' => Url::emailToUtf8($user->email, false),
			'{id}' => $user->user_id,
		];
	}

	public function canCancel()
	{
		return true;
	}

	public function canTriggerByChoice()
	{
		return false;
	}
}
