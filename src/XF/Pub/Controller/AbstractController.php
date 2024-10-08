<?php

namespace XF\Pub\Controller;

use XF\ControllerPlugin\ErrorPlugin;
use XF\Db\DuplicateKeyException;
use XF\Entity\SessionActivity;
use XF\Entity\UserConfirmation;
use XF\Mvc\Controller;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\Message;
use XF\Mvc\Reply\Redirect;
use XF\Mvc\Reply\Reroute;
use XF\Mvc\Reply\View;
use XF\PreEscaped;
use XF\Pub\App;
use XF\Repository\BanningRepository;
use XF\Repository\IpRepository;
use XF\Repository\SessionActivityRepository;
use XF\Service\FloodCheckService;
use XF\Service\User\SecurityLockResetService;
use XF\Util\Ip;
use XF\Util\Url;

use function boolval, get_class, in_array, is_array, strlen;

abstract class AbstractController extends Controller
{
	protected function preDispatchType($action, ParameterBag $params)
	{
		$this->checkTfaRedirect();

		$this->assertCorrectVersion($action);
		$this->assertIpNotBanned();
		$this->assertNotBanned();
		$this->assertNotRejected($action);
		$this->assertNotDisabled($action);
		$this->assertCanonicalBaseUrl($action);
		$this->assertViewingPermissions($action);
		$this->assertBoardActive($action);
		$this->assertTfaRequirement($action);
		$this->assertNotSecurityLocked($action);
		$this->assertPolicyAcceptance($action);

		if ($this->isDiscouraged())
		{
			$this->discourage($action);
		}

		$this->preDispatchController($action, $params);
	}

	protected function preDispatchController($action, ParameterBag $params)
	{
	}

	protected function postDispatchType($action, ParameterBag $params, AbstractReply &$reply)
	{
		$this->postDispatchController($action, $params, $reply);

		$this->updateSessionActivity($action, $params, $reply);

		$isCacheable = ($reply instanceof View || $reply instanceof Reroute);
		if (!$isCacheable)
		{
			// don't allow caching of anything other than normal views to prevent accidental caching
			// of errors or temporary messages
			App::$allowPageCache = false;
		}
	}

	protected function postDispatchController($action, ParameterBag $params, AbstractReply &$reply)
	{
	}

	protected function updateSessionActivity($action, ParameterBag $params, AbstractReply &$reply)
	{
		if ($this->canUpdateSessionActivity($action, $params, $reply, $viewState))
		{
			$controller = $this->app->extension()->resolveExtendedClassToRoot($this);

			// log these details for page caching regardless of whether we want to update for this request
			$reply->setViewOption('sessionActivity', [
				'controller' => $controller,
				'action' => $action,
				'params' => $params->params(),
				'viewState' => $viewState,
			]);

			if ($this->request->isPrefetch())
			{
				// never update the session activity for this; the user didn't see it
				return;
			}

			/** @var SessionActivityRepository $activityRepo */
			$activityRepo = $this->repository(SessionActivityRepository::class);
			$activityRepo->updateSessionActivity(
				\XF::visitor()->user_id,
				$this->request->getIp(),
				$controller,
				$action,
				$params->params(),
				$viewState,
				$this->request->getRobotName()
			);
		}
	}

	protected function canUpdateSessionActivity($action, ParameterBag $params, AbstractReply &$reply, &$viewState)
	{
		// don't update session activity for an AJAX request
		if ($this->request->isXhr())
		{
			return false;
		}

		$viewState = 'error';

		switch (get_class($reply))
		{
			case Redirect::class:
			case Reroute::class:
				return false; // don't update anything, assume the next page will do it

			case Message::class:
			case View::class:
				$viewState = 'valid';
				break;
		}

		if ($reply->getResponseCode() >= 400)
		{
			$viewState = 'error';
		}

		return true;
	}

	public function checkTfaRedirect()
	{
		$session = $this->session();
		if ($session->tfaLoginRedirect)
		{
			unset($session->tfaLoginRedirect);

			if (\XF::visitor()->user_id || !$session->tfaLoginUserId)
			{
				return;
			}

			throw $this->exception($this->redirect($this->buildLink('login/two-step', null, [
				'_xfRedirect' => $this->request->getFullRequestUri(),
				'remember' => 1,
			])));
		}
	}

	public function assertRegistrationRequired()
	{
		if (!\XF::visitor()->user_id)
		{
			throw $this->exception(
				$this->plugin(ErrorPlugin::class)->actionRegistrationRequired()
			);
		}
	}

	public function assertIpNotBanned()
	{
		$bannedIps = $this->app()->container('bannedIps');
		$result = Ip::checkIpsAgainstBinaryRangeList($this->request->getAllIps(), $bannedIps['data']);

		if (is_array($result))
		{
			/** @var BanningRepository $repo */
			$repo = $this->repository(BanningRepository::class);

			$matched = $repo->findIpMatchesByRange($result[0], $result[1])
				->where('match_type', 'banned');

			foreach ($matched->fetch() AS $match)
			{
				$match->fastUpdate('last_triggered_date', time());
			}
		}
		if ($result)
		{
			throw $this->exception(
				$this->plugin(ErrorPlugin::class)->actionBannedIp()
			);
		}
	}

	public function assertNotBanned()
	{
		if (\XF::visitor()->is_banned)
		{
			throw $this->exception(
				$this->plugin(ErrorPlugin::class)->actionBanned()
			);
		}
	}

	public function assertNotRejected($action)
	{
		if (\XF::visitor()->user_state == 'rejected')
		{
			throw $this->exception(
				$this->plugin(ErrorPlugin::class)->actionRejected()
			);
		}
	}

	public function assertNotDisabled($action)
	{
		if (\XF::visitor()->user_state == 'disabled')
		{
			throw $this->exception(
				$this->plugin(ErrorPlugin::class)->actionDisabled()
			);
		}
	}

	public function assertCanonicalBaseUrl($action)
	{
		if ($this->responseType != 'html')
		{
			return;
		}

		if (!$this->request->isGet() && !$this->request->isHead())
		{
			return;
		}

		$options = $this->options();
		if (!$options->boardUrlCanonical)
		{
			return;
		}

		$request = $this->request;
		$boardUrl = rtrim($options->boardUrl, '/');
		$fullBasePath = rtrim($request->getFullBasePath(), '/');

		if ($fullBasePath == $options->boardUrl)
		{
			// the URL is already canonical
			return;
		}

		$requestUri = $request->getFullRequestUri();

		if (strpos($requestUri, $fullBasePath) === 0)
		{
			$extendedPath = ltrim(substr($requestUri, strlen($fullBasePath)), '/');
			$newUrl = $boardUrl . '/' . $extendedPath;
			throw $this->exception($this->redirectPermanently($newUrl));
		}
	}

	public function assertViewingPermissions($action)
	{
		if (!\XF::visitor()->hasPermission('general', 'view'))
		{
			$reply = $this->noPermission();
			$reply->setPageParam('skipSidebarWidgets', true);

			throw $this->exception($reply);
		}
	}

	public function assertBoardActive($action)
	{
		$options = $this->options();
		if (!$options->boardActive && !\XF::visitor()->is_admin)
		{
			$reply = $this->message(new PreEscaped($options->boardInactiveMessage), $this->app->config('serviceUnavailableCode'));
			$reply->setPageParam('skipSidebarWidgets', true);
			$reply->setResponseHeader('Retry-After', 60);
			$reply->setResponseHeader('Refresh', 60);

			throw $this->exception($reply);
		}
	}

	public function assertTfaRequirement($action)
	{
		$visitor = \XF::visitor();
		if ($visitor->user_id
			&& empty($visitor->Option->use_tfa)
			&& \XF::config('enableTfa')
			&& $visitor->hasPermission('general', 'requireTfa')
		)
		{
			$reply = $this->message(\XF::phrase('you_must_enable_two_step_to_continue', [
				'link' => $this->buildLink('account/two-step'),
			]));
			$reply->setPageParam('skipSidebarWidgets', true);

			throw $this->exception($reply);
		}
	}

	public function assertNotSecurityLocked($action)
	{
		$visitor = \XF::visitor();
		if ($visitor->user_id && $visitor->security_lock)
		{
			switch ($visitor->security_lock)
			{
				case 'change':

					throw $this->exception($this->redirect($this->buildLink('account/security', null, [
						'_xfRedirect' => $this->request->getFullRequestUri(),
					])));

				case 'reset':

					$existing = $this->em()->find(UserConfirmation::class, [$visitor->user_id, 'security_lock_reset']);

					if ($existing)
					{
						$reply = $this->message(
							\XF::phrase('your_account_is_currently_security_locked_and_awaiting_password_reset', [
								'email' => Url::emailToUtf8($visitor->email, false),
								'resendLink' => $this->buildLink('security-lock/resend', $visitor),
							])
						);
						$reply->setPageParam('skipSidebarWidgets', true);

						throw $this->exception($reply);
					}
					else
					{
						/** @var SecurityLockResetService $passwordConfirmation */
						$passwordConfirmation = $this->service(SecurityLockResetService::class, $visitor);

						if (!$passwordConfirmation->canTriggerConfirmation($error))
						{
							throw $this->exception($this->error($error));
						}

						try
						{
							$passwordConfirmation->triggerConfirmation();
						}
						catch (DuplicateKeyException $e)
						{
							// Likely a race condition with another tab. We can just ignore it
							// as the message will direct them to check their email.
						}

						$reply = $this->message(
							\XF::phrase('your_account_is_currently_security_locked_need_to_reset_your_password')
								. ' '
								. \XF::phrase('password_reset_request_has_been_emailed_to_you')
						);
						$reply->setPageParam('skipSidebarWidgets', true);

						throw $this->exception($reply);
					}
			}
		}
	}

	public function assertPolicyAcceptance($action)
	{
		$options = $this->options();

		if (!isset($options->privacyPolicyLastUpdate, $options->termsLastUpdate))
		{
			return;
		}

		$request = $this->request;
		$requestUri = $request->getFullRequestUri();
		$visitor = \XF::visitor();

		$privacyLastUpdate = $options->privacyPolicyLastUpdate;
		$privacyPolicyUrl = $this->app->container('privacyPolicyUrl');

		$termsLastUpdate = $options->termsLastUpdate;
		$tosUrl = $this->app->container('tosUrl');

		if ($privacyLastUpdate
			&& $privacyPolicyUrl
			&& $visitor->user_id
			&& $visitor->privacy_policy_accepted < $privacyLastUpdate
			&& !$visitor->security_lock
		)
		{
			// check if requested route matches privacy policy URL or whitelist to bypass acceptance
			if (!empty($options->privacyPolicyUrl['custom'])
				&& $this->canBypassPolicyAcceptance(
					$options->privacyPolicyForceWhitelist,
					$privacyPolicyUrl,
					$requestUri
				)
			)
			{
				return;
			}

			throw $this->exception($this->redirect($this->buildLink('misc/accept-privacy-policy', null, [
				'_xfRedirect' => $this->request->getFullRequestUri(),
			]), ''));
		}
		else if ($termsLastUpdate
			&& $tosUrl
			&& $visitor->user_id
			&& $visitor->terms_accepted < $termsLastUpdate
			&& !$visitor->security_lock
		)
		{
			// check if requested route matches terms URL or whitelist to bypass acceptance
			if (!empty($options->tosUrl['custom'])
				&& $this->canBypassPolicyAcceptance(
					$options->tosForceWhitelist,
					$tosUrl,
					$requestUri
				)
			)
			{
				return;
			}

			throw $this->exception($this->redirect($this->buildLink('misc/accept-terms', null, [
				'_xfRedirect' => $this->request->getFullRequestUri(),
			]), ''));
		}
	}

	protected function canBypassPolicyAcceptance($whitelist, $policyUrl, $requestUri)
	{
		$request = $this->request;

		if ($whitelist)
		{
			$whitelistRoutePaths = preg_split('/\s+/', trim($whitelist), -1, PREG_SPLIT_NO_EMPTY);
		}
		else
		{
			$whitelistRoutePaths = [];
		}

		$whitelistRoutePaths[] = $request->getRoutePathFromUrl($policyUrl);
		$whitelistRoutePaths = array_map(function ($routePath)
		{
			return rtrim($routePath, '/') . '/';
		}, $whitelistRoutePaths);

		$requestRoutePath = rtrim($request->getRoutePathFromUrl($requestUri), '/') . '/';

		return in_array($requestRoutePath, $whitelistRoutePaths, true);
	}

	public function isEmbeddedImageRequest(): bool
	{
		return $this->request->isEmbeddedImageRequest();
	}

	public function assertNotEmbeddedImageRequest()
	{
		if ($this->isEmbeddedImageRequest())
		{
			$this->setResponseType('raw');

			$view = $this->view('XF:Error\EmbeddedImageRequest');
			$view->setResponseCode(406);

			throw $this->exception($view);
		}
	}

	public function hasContentPendingApproval()
	{
		$pendingUntil = $this->session()->hasContentPendingUntil;
		return ($pendingUntil && $pendingUntil >= \XF::$time);
	}

	protected function isDiscouraged()
	{
		$visitor = \XF::visitor();
		if ($visitor->user_id && $visitor->Option->is_discouraged)
		{
			return true;
		}
		else
		{
			$discouragedIps = $this->app()->container('discouragedIps');
			$result = Ip::checkIpsAgainstBinaryRangeList($this->request->getAllIps(), $discouragedIps['data']);

			if (is_array($result))
			{
				/** @var BanningRepository $repo */
				$repo = $this->repository(BanningRepository::class);

				$matched = $repo->findIpMatchesByRange($result[0], $result[1])
					->where('match_type', 'discouraged');

				foreach ($matched->fetch() AS $match)
				{
					$match->fastUpdate('last_triggered_date', time());
				}
			}
			return (bool) $result;
		}
	}

	protected $discourageChecked;

	/**
	 * Discourage the current visitor from remaining on the board by making theirs a bad experience.
	 *
	 * @param string $action
	 */
	protected function discourage($action)
	{
		if ($this->discourageChecked === true)
		{
			return;
		}
		$this->discourageChecked = true;

		$options = $this->app()->options();

		// random loading delay
		if ($options->discourageDelay['max'])
		{
			usleep(mt_rand($options->discourageDelay['min'], $options->discourageDelay['max']) * 1000000);
		}

		// random page redirect
		if ($options->discourageRedirectChance && mt_rand(0, 100) < $options->discourageRedirectChance)
		{
			header('Location: ' . ($options->discourageRedirectUrl ?: $options->boardUrl));
			die();
		}

		// random blank page
		if ($options->discourageBlankChance && mt_rand(0, 100) < $options->discourageBlankChance)
		{
			die();
		}

		// randomly disable search
		if ($options->discourageSearchChance && mt_rand(0, 100) < $options->discourageSearchChance)
		{
			$options->enableSearch = false;
		}

		// increase flood check time
		if ($options->discourageFloodMultiplier > 1)
		{
			$options->floodCheckLength = $options->floodCheckLength * $options->discourageFloodMultiplier;
		}
	}

	public function assertNotFlooding($action, $floodingLimit = null)
	{
		$visitor = \XF::visitor();
		if ($visitor->hasPermission('general', 'bypassFloodCheck'))
		{
			return;
		}

		/** @var FloodCheckService $floodChecker */
		$floodChecker = $this->service(FloodCheckService::class);
		$timeRemaining = $floodChecker->checkFlooding($action, $visitor->user_id, $floodingLimit);
		if ($timeRemaining)
		{
			throw $this->exception($this->responseFlooding($timeRemaining));
		}
	}

	public function responseFlooding($floodSeconds)
	{
		return $this->error(\XF::phrase('must_wait_x_seconds_before_performing_this_action', ['count' => $floodSeconds]));
	}

	public function isRobot()
	{
		return boolval($this->request->getRobotName());
	}

	/**
	 * @return IpRepository
	 */
	protected function getIpRepo()
	{
		return $this->repository(IpRepository::class);
	}

	protected static function getActivityDetailsForContent(
		array $activities,
		$phrase,
		$pluckParam,
		\Closure $dataLoader,
		$fallbackPhrase = null
	)
	{
		$ids = [];

		foreach ($activities AS $activity)
		{
			/** @var SessionActivity $activity */
			$id = $activity->pluckParam($pluckParam);
			if ($id)
			{
				$ids[$id] = $id;
			}
		}

		if ($ids)
		{
			$data = $dataLoader($ids);

		}
		else
		{
			$data = [];
		}

		$output = [];

		foreach ($activities AS $key => $activity)
		{
			/** @var SessionActivity $activity */
			$id = $activity->pluckParam($pluckParam);

			$content = $id && isset($data[$id]) ? $data[$id] : null;
			if ($content)
			{
				$output[$key] = [
					'description' => $phrase,
					'title' => $content['title'],
					'url' => $content['url'],
				];
			}
			else if ($id)
			{
				$output[$key] = $phrase;
			}
			else
			{
				$output[$key] = $fallbackPhrase ?: $phrase;
			}
		}

		return $output;
	}

	/**
	 * @param SessionActivity[] $activities
	 */
	public static function getActivityDetails(array $activities)
	{
		return false;
	}
}
