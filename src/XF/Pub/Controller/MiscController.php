<?php

namespace XF\Pub\Controller;

use XF\ControllerPlugin\CodeEditorPlugin;
use XF\ControllerPlugin\StylePlugin;
use XF\CookieConsent;
use XF\Entity\HelpPage;
use XF\Finder\HelpPageFinder;
use XF\Mvc\Reply\AbstractReply;
use XF\Repository\EmojiRepository;
use XF\Repository\LanguageRepository;
use XF\Repository\StyleRepository;
use XF\Repository\TagRepository;
use XF\Repository\UserPushRepository;
use XF\Service\ContactService;
use XF\Util\Str;

use XF\Validator\Username;

use function count, strlen;

class MiscController extends AbstractController
{
	public function actionCookies(): AbstractReply
	{
		if ($this->app()->cookieConsent()->getMode() !== CookieConsent::MODE_ADVANCED)
		{
			return $this->notFound();
		}

		if ($this->filter('update', 'bool'))
		{
			if (!$this->request->isPost())
			{
				$this->assertValidCsrfToken($this->filter('t', 'str'));
			}

			$request = $this->app()->request();
			$response = $this->app()->response();
			$cookieConsent = $this->app()->cookieConsent();

			if ($this->filter('accept', 'bool'))
			{
				$cookieConsent->addConsentedGroups(
					$cookieConsent->getGroups(false)
				);
			}
			else if ($this->filter('reject', 'bool'))
			{
				$cookieConsent->removeConsentedGroups(
					$cookieConsent->getGroups(false)
				);
			}
			else
			{
				$consent = array_keys($this->filter('consent', 'array-bool'));

				if ($this->filter('add', 'bool'))
				{
					$cookieConsent->addConsentedGroups($consent);
				}
				else if ($this->filter('remove', 'bool'))
				{
					$cookieConsent->removeConsentedGroups($consent);
				}
				else
				{
					$cookieConsent->removeConsentedGroups(
						$cookieConsent->getConsentedGroups()
					);
					$cookieConsent->addConsentedGroups($consent);
				}
			}

			$cookieConsent->applyConsentPreferences($request, $response);
			$unconsentedLocalStorage = $cookieConsent->getUnconsentedCookies(
				function (array $config, string $key)
				{
					return $config['localStorage'];
				}
			);

			$reply = $this->redirect(
				$this->getDynamicRedirectIfNot($this->buildLink('misc/cookies'))
			);
			$reply->setJsonParam(
				'unconsented_local_storage',
				$unconsentedLocalStorage
			);
			$reply->setJsonParam(
				'group_consent_state',
				$cookieConsent->getGroupConsentState()
			);
			return $reply;
		}

		$viewParams = [];
		return $this->view('XF:Misc\Cookies', 'misc_cookies', $viewParams);
	}

	/**
	 * @return ContactService
	 */
	protected function setupContactService()
	{
		/** @var ContactService $contactService */
		$contactService = $this->service(ContactService::class);

		$visitor = \XF::visitor();

		$input = $this->filter([
			'username' => 'str',
			'email' => 'str',
			'subject' => 'str',
			'message' => 'str',
		]);

		if ($visitor->user_id)
		{
			$contactService->setFromUser($visitor);
			if (!$visitor->email)
			{
				if (!$contactService->setEmail($input['email'], $error))
				{
					throw $this->exception($this->error($error));
				}
			}
		}
		else
		{
			$contactService->setFromGuest($input['username'], $input['email']);
		}

		$contactService
			->setMessageDetails($input['subject'], $input['message'])
			->setFromIp($this->request->getIp());

		return $contactService;
	}

	public function actionContact()
	{
		$options = $this->options();
		if ($options->contactUrl['type'] == 'custom')
		{
			return $this->redirect($options->contactUrl['custom'], '');
		}
		else if (!$options->contactUrl['type'])
		{
			return $this->redirect($this->buildLink('index'));
		}

		if (!\XF::visitor()->canUseContactForm())
		{
			return $this->noPermission();
		}

		$this->assertCanonicalUrl($this->buildLink('misc/contact'));

		$redirect = $this->getDynamicRedirect(null, false);
		$forceCaptcha = \XF::visitor()->user_state != 'valid';
		$this->assertCaptchaCookieConsent(null, $forceCaptcha);

		if ($this->isPost())
		{
			$contactService = $this->setupContactService();

			if (!$this->captchaIsValid($forceCaptcha))
			{
				return $this->error(\XF::phrase('did_not_complete_the_captcha_verification_properly'));
			}

			$contactService->checkForSpam();

			if (!$contactService->validate($errors))
			{
				return $this->error($errors);
			}

			$this->assertNotFlooding('contact');

			$contactService->send();

			return $this->redirect($redirect, \XF::phrase('your_message_has_been_sent'));
		}
		else
		{
			$viewParams = [
				'redirect' => $redirect,
				'forceCaptcha' => $forceCaptcha,
			];
			return $this->view('XF:Misc\Contact', 'contact_form', $viewParams);
		}
	}

	public function actionLanguage()
	{
		$visitor = \XF::visitor();
		if (!$visitor->canChangeLanguage($error))
		{
			return $this->noPermission($error);
		}

		$redirect = $this->getDynamicRedirect(null, true);

		if ($this->request->exists('language_id'))
		{
			$this->assertValidCsrfToken($this->filter('t', 'str'));

			$language = $this->app->language($this->filter('language_id', 'uint'));

			if ($language->isUsable($visitor))
			{
				if ($visitor->user_id)
				{
					$visitor->language_id = $language->getId();
					$visitor->save();

					$this->app->response()->setCookie('language_id', false);
				}
				else
				{
					$this->app->response()->setCookie('language_id', $language->getId());
				}
			}
			return $this->redirect($redirect);
		}
		else
		{
			$viewParams = [
				'redirect' => $redirect,
				'languages' => $this->repository(LanguageRepository::class)->getUserSelectableLanguages(),
			];
			return $this->view('XF:Misc\Language', 'language_chooser', $viewParams);
		}
	}

	public function actionStyle()
	{
		$visitor = \XF::visitor();
		if (!$visitor->canChangeStyle($error))
		{
			return $this->noPermission($error);
		}

		$redirect = $this->getDynamicRedirect(null, true);

		$csrfValid = true;
		if ($visitor->user_id)
		{
			$csrfValid = $this->validateCsrfToken($this->filter('t', 'str'));
		}

		if ($this->request->exists('style_id') && $csrfValid)
		{
			$styleId = $this->filter('style_id', 'uint');
			$style = $this->app->style($styleId);

			if ($style['user_selectable'] || $visitor->is_admin)
			{
				$stylePlugin = $this->plugin(StylePlugin::class);
				$currentStyle = $this->app->style($visitor->style_id);
				$variation = $stylePlugin->getEquivalentStyleVariation(
					$currentStyle,
					$style,
					$visitor->style_variation
				);

				if ($visitor->user_id)
				{
					$visitor->style_id = $styleId;
					$visitor->style_variation = $variation;
					$visitor->save();

					$this->app->response()->setCookie('style_id', false);
					$this->app->response()->setCookie('style_variation', false);
				}
				else
				{
					$this->app->response()->setCookie('style_id', $style->getId());
					$this->app->response()->setCookie('style_variation', $variation);
				}
			}
			return $this->redirect($redirect);
		}
		else
		{
			$styles = $this->repository(StyleRepository::class)->getUserSelectableStyles();

			$styleId = $this->filter('style_id', 'uint');
			if ($styleId && !empty($styles[$styleId]['user_selectable']))
			{
				$style = $styles[$styleId];
			}
			else
			{
				$style = false;
			}

			$viewParams = [
				'redirect' => $redirect,
				'style' => $style,
				'styles' => $styles,
			];
			return $this->view('XF:Misc\Style', 'style_chooser', $viewParams);
		}
	}

	public function actionStyleVariation(): AbstractReply
	{
		$visitor = \XF::visitor();
		$style = $this->app->style($visitor->style_id);
		if (!$visitor->canChangeStyleVariation($style, $error))
		{
			return $this->noPermission($error);
		}

		$redirect = $this->getDynamicRedirectIfNot(
			$this->buildLink('misc/style-variation')
		);

		$saveCallback = function (string $variation) use ($visitor): void
		{
			if ($visitor->user_id)
			{
				$visitor->style_variation = $variation;
				$visitor->save();

				$this->app->response()->setCookie('style_variation', false);
			}
			else
			{
				$this->app->response()->setCookie('style_variation', $variation);
			}
		};

		$stylePlugin = $this->plugin(StylePlugin::class);
		return $stylePlugin->actionStyleVariation(
			$style,
			$redirect,
			$saveCallback
		);
	}

	public function actionStyleVariationInput(): AbstractReply
	{
		$this->assertPostOnly();

		$visitor = \XF::visitor();

		if (!$visitor->canChangeStyle($error))
		{
			return $this->noPermission($error);
		}

		$styleId = $this->filter('style_id', 'int');
		$newStyle = $this->app->style($styleId);
		if (!$newStyle['user_selectable'] && !$visitor->is_admin)
		{
			return $this->noPermission();
		}

		$currentStyle = $this->app->style($visitor->style_id);
		$variation = $this->filter('style_variation', 'str')
			?: $visitor->style_variation;

		$stylePlugin = $this->plugin(StylePlugin::class);
		$variation = $stylePlugin->getEquivalentStyleVariation(
			$currentStyle,
			$newStyle,
			$variation
		);

		$viewParams = [
			'style' => $newStyle,
			'variation' => $variation,
		];
		return $this->view(
			'XF:Misc\StyleVariationInput',
			'style_variation_input',
			$viewParams
		);
	}

	public function actionCaptcha()
	{
		$this->assertCaptchaCookieConsent(null, true);

		$withRow = $this->filter('with_row', 'bool');
		$context = $this->filter('context', 'str');
		$rowType = preg_replace('#[^a-z0-9_ -]#i', '', $this->filter('row_type', 'str'));

		return $this->view('XF:Misc\Captcha', 'captcha', [
			'withRow' => $withRow,
			'rowType' => $rowType,
			'context' => $context,
		]);
	}

	public function actionIpInfo()
	{
		if (!\XF::visitor()->canViewIps())
		{
			return $this->noPermission();
		}

		$ip = $this->filter('ip', 'str');
		$url = $this->options()->ipInfoUrl;

		if (strpos($url, '{ip}') === false)
		{
			$url = 'https://whatismyipaddress.com/ip/{ip}';
		}

		return $this->redirectPermanently(str_replace('{ip}', urlencode($ip), $url));
	}

	public function actionLocationInfo()
	{
		$location = $this->filter('location', 'str');

		$url = $this->options()->geoLocationUrl;
		if (strpos($url, '{location}') === false)
		{
			$url = 'https://maps.google.com/maps?q={location}';
		}

		return $this->redirectPermanently(str_replace('{location}', urlencode($location), $url));
	}

	public function actionTagAutoComplete()
	{
		if (!$this->options()->enableTagging)
		{
			return $this->noPermission();
		}

		$tagRepo = $this->repository(TagRepository::class);

		$q = $this->filter('q', 'str');
		$q = $tagRepo->normalizeTag($q);

		if (strlen($q) >= 2)
		{
			$tags = $this->repository(TagRepository::class)->getTagAutoCompleteResults($q);

			$results = [];
			foreach ($tags AS $tag)
			{
				$results[] = [
					'id' => $tag->tag,
					'text' => $tag->tag,
					'q' => $q,
				];
			}
		}
		else
		{
			$results = [];
		}
		$view = $this->view();
		$view->setJsonParam('results', $results);
		return $view;
	}

	public function actionCodeEditorModeLoader()
	{
		$language = $this->filter('language', 'str');

		/** @var CodeEditorPlugin $plugin */
		$plugin = $this->plugin(CodeEditorPlugin::class);

		return $plugin->actionModeLoader($language);
	}

	public function actionAcceptPrivacyPolicy()
	{
		$visitor = \XF::visitor();
		$lastUpdate = $this->options()->privacyPolicyLastUpdate;

		if (!$visitor->user_id || !$lastUpdate)
		{
			return $this->noPermission();
		}

		if ($visitor->privacy_policy_accepted > $lastUpdate)
		{
			return $this->redirect(
				$this->getDynamicRedirectIfNot(
					$this->buildLink('misc/accept-privacy-policy')
				)
			);
		}

		if ($this->isPost())
		{
			if (!$this->filter('accept', 'bool'))
			{
				return $this->error(\XF::phrase('please_read_and_accept_our_privacy_policy_before_continuing'));
			}

			$visitor->privacy_policy_accepted = time();
			$visitor->save();

			return $this->redirect($this->getDynamicRedirect());
		}
		else
		{
			$privacyPolicyOption = $this->options()->privacyPolicyUrl;

			$viewParams = [
				'privacyPolicyOption' => $privacyPolicyOption,
			];

			if ($privacyPolicyOption['type'] == 'default')
			{
				/** @var HelpPage $page */
				$page = $this->finder(HelpPageFinder::class)
					->where('page_name', 'privacy-policy')
					->fetchOne();
				if (!$page)
				{
					return $this->notFound();
				}

				$viewParams['page'] = $page;
				$viewParams['templateName'] = 'public:_help_page_' . $page->page_id;
			}

			return $this->view('XF:Misc\AcceptPrivacyPolicy', 'accept_privacy_policy', $viewParams);
		}
	}

	public function actionAcceptTerms()
	{
		$visitor = \XF::visitor();
		$lastUpdate = $this->options()->termsLastUpdate;

		if (!$visitor->user_id || !$lastUpdate)
		{
			return $this->noPermission();
		}

		if ($visitor->terms_accepted > $lastUpdate)
		{
			return $this->redirect(
				$this->getDynamicRedirectIfNot(
					$this->buildLink('misc/accept-terms')
				)
			);
		}

		if ($this->isPost())
		{
			if (!$this->filter('accept', 'bool'))
			{
				return $this->error(\XF::phrase('please_read_and_accept_our_terms_and_rules_before_continuing'));
			}

			$visitor->terms_accepted = time();
			$visitor->save();

			return $this->redirect($this->getDynamicRedirect());
		}
		else
		{
			$termsOption = $this->options()->tosUrl;

			$viewParams = [
				'termsOption' => $termsOption,
			];

			if ($termsOption['type'] == 'default')
			{
				/** @var HelpPage $page */
				$page = $this->finder(HelpPageFinder::class)
					->where('page_name', 'terms')
					->fetchOne();
				if (!$page)
				{
					return $this->notFound();
				}

				$viewParams['page'] = $page;
				$viewParams['templateName'] = 'public:_help_page_' . $page->page_id;
			}

			return $this->view('XF:Misc\AcceptTerms', 'accept_terms', $viewParams);
		}
	}

	public function actionUpdatePushSubscription()
	{
		$this->assertPostOnly();

		$visitor = \XF::visitor();

		$subscription = $this->filter([
			'endpoint' => 'str',
			'unsubscribed' => 'bool',
			'key' => 'str',
			'token' => 'str',
			'encoding' => 'str',
		]);

		/** @var UserPushRepository $userPushRepo */
		$userPushRepo = $this->repository(UserPushRepository::class);

		if (!$userPushRepo->validateSubscriptionDetails($subscription, $validationError))
		{
			return $this->error($validationError ?: \XF::phrase('invalid_subscription_endpoint_provided'));
		}

		if ($subscription['unsubscribed'])
		{
			if ($visitor->user_id)
			{
				$userPushRepo->deleteUserPushSubscription($visitor, $subscription);
			}
			else
			{
				$userPushRepo->deletePushSubscription($subscription);
			}
		}
		else if ($visitor->user_id && $this->options()->enablePush)
		{
			$userPushRepo->insertUserPushSubscription($visitor, $subscription);
			$userPushRepo->limitUserPushSubscriptionCount($visitor, 20);
		}

		return $this->message(\XF::phrase('push_subscription_updated_successfully'));
	}

	public function actionFindEmoji()
	{
		$q = ltrim($this->filter('q', 'str', ['no-trim']));
		$excludeSmilies = $this->filter('exclude_smilies', 'bool');
		$insertEmoji = $this->filter('insert_emoji', 'bool');

		if ($q !== '' && Str::strlen($q) >= 2)
		{
			/** @var EmojiRepository $emojiRepo */
			$emojiRepo = $this->repository(EmojiRepository::class);
			$results = $emojiRepo->getMatchingEmojiByString($q, [
				'includeSmilies' => !$excludeSmilies,
				'nativeEmoji' => $insertEmoji,
			]);
		}
		else
		{
			$results = [];
			$q = '';
		}

		$viewParams = [
			'q' => $q,
			'results' => $results,
		];
		return $this->view('XF:Misc\FindEmoji', '', $viewParams);
	}

	public function actionValidateUsername()
	{
		$this->assertPostOnly();

		$username = $this->filter('content', 'str');

		$errors = [];

		$usernameValidator = $this->app->validator(Username::class);

		$visitor = \XF::visitor();
		if ($visitor->user_id)
		{
			$usernameValidator->setOption('self_user_id', $visitor->user_id);
		}

		if (!$usernameValidator->isValid($username, $errorKey))
		{
			$errors[] = $usernameValidator->getPrintableErrorValue($errorKey);
		}

		$view = $this->view('XF:Misc\ValidateUsername');
		$view->setJsonParams([
			'inputValid' => !count($errors),
			'inputErrors' => $errors,
			'validatedValue' => $username,
		]);
		return $view;
	}

	public static function getActivityDetails(array $activities)
	{
		$output = [];

		foreach ($activities AS $key => $activity)
		{
			if (strtolower($activity->controller_action) == 'contact')
			{
				$output[$key] = \XF::phrase('contacting_staff');
			}
			else
			{
				$output[$key] = false;
			}
		}

		return $output;
	}

	public function assertNotRejected($action)
	{
		if (strtolower($action) == 'contact')
		{
			// bypass rejection for the default contact form
		}
		else
		{
			parent::assertNotRejected($action);
		}
	}

	public function assertNotDisabled($action)
	{
		if (strtolower($action) == 'contact')
		{
			// bypass disabled notice for the default contact form
		}
		else
		{
			parent::assertNotRejected($action);
		}
	}

	public function assertNotSecurityLocked($action)
	{
		if (strtolower($action) == 'contact')
		{
			// bypass security lock for the default contact form
		}
		else
		{
			parent::assertNotSecurityLocked($action);
		}
	}

	public function assertViewingPermissions($action)
	{
		switch (strtolower($action))
		{
			case 'captcha':
			case 'contact':
			case 'validateusername':
				break;

			default:
				parent::assertViewingPermissions($action);
		}
	}

	public function assertPolicyAcceptance($action)
	{
	}
}
