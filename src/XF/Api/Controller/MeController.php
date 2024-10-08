<?php

namespace XF\Api\Controller;

use XF\Api\ControllerPlugin\UserPlugin;
use XF\Entity\UserProfile;
use XF\Mvc\Entity\Entity;
use XF\Mvc\ParameterBag;
use XF\Service\User\EmailChangeService;
use XF\Service\User\PasswordChangeService;
use XF\Service\User\SignatureEditService;

/**
 * @api-group Me
 */
class MeController extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		if ($this->request->getRequestMethod() !== 'get')
		{
			$this->assertApiScope('user:write');
			$this->assertRegisteredUser();
		}
	}

	/**
	 * @api-desc Gets information about the current API user
	 *
	 * @api-out User $me
	 */
	public function actionGet()
	{
		$tokenOrKey = \XF::accessToken() ?? \XF::apiKey();
		$verbosity = $tokenOrKey->hasScope('user:read') ? Entity::VERBOSITY_VERBOSE : Entity::VERBOSITY_QUIET;

		return $this->apiResult([
			'me' => \XF::visitor()->toApiResult($verbosity),
		]);
	}

	/**
	 * @api-desc Updates information about the current user
	 *
	 * @api-see XF\Api\ControllerPlugin\User::getAccountEditInput()
	 * @api-in string $custom_fields[<name>]
	 *
	 * @api-out true $success
	 */
	public function actionPost()
	{
		if (\XF::isApiCheckingPermissions() && !\XF::visitor()->canEditProfile())
		{
			return $this->noPermission();
		}

		$this->selfSaveProcess()->run();

		return $this->apiSuccess();
	}

	protected function selfSaveProcess()
	{
		$form = $this->formAction();
		$visitor = \XF::visitor();

		/** @var UserPlugin $userPlugin */
		$userPlugin = $this->plugin(UserPlugin::class);

		$tableInput = $userPlugin->getAccountEditInput();
		// TODO: DoB

		if (\XF::isApiCheckingPermissions() && !$visitor->hasPermission('general', 'editCustomTitle'))
		{
			unset($tableInput['user']['custom_title']);
		}

		if (\XF::isApiCheckingPermissions() && isset($tableInput['profile']['signature']))
		{
			$signature = $tableInput['profile']['signature'];
			unset($tableInput['profile']['signature']);

			if ($visitor->canEditSignature())
			{
				/** @var SignatureEditService $sigEditor */
				$sigEditor = $this->service(SignatureEditService::class, $visitor);
				if ($sigEditor->setSignature($signature, $errors))
				{
					$tableInput['profile']['signature'] = $sigEditor->getNewSignature();
				}
				else
				{
					$form->logErrors($errors);
				}
			}
		}

		/** @var UserProfile $profile */
		$profile = $visitor->getRelationOrDefault('Profile');

		$userPlugin->saveEditFormFromTableInput($form, $visitor, $tableInput);
		$userPlugin->customFieldsSaveProcess($form, $profile);

		if (isset($tableInput['profile']['about']) && \XF::isApiCheckingPermissions() && $visitor->isSpamCheckRequired())
		{
			$checker = $this->app()->spam()->contentChecker();
			$checker->check($visitor, $tableInput['profile']['about'], [
				'content_type' => 'user',
				'content_id' => $visitor->user_id,
			]);

			$decision = $checker->getFinalDecision();
			switch ($decision)
			{
				case 'moderated':
				case 'denied':
					$checker->logSpamTrigger('user_about', $visitor->user_id);
					$form->logError(\XF::phrase('your_content_cannot_be_submitted_try_later'));
					break;
			}
		}

		return $form;
	}

	/**
	 * @api-desc Updates the current user's email address
	 *
	 * @api-in str $current_password <req>
	 * @api-in str $email <req>
	 *
	 * @api-out true $success
	 * @api-out bool $confirmation_required True if email confirmation is required for this change
	 */
	public function actionPostEmail()
	{
		$visitor = \XF::visitor();
		$auth = $visitor->Auth->getAuthenticationHandler();
		if (!$auth)
		{
			return $this->noPermission();
		}

		$this->assertRequiredApiInput(['current_password', 'email']);

		$password = $this->filter('current_password', 'str');
		$email = $this->filter('email', 'str');

		/** @var EmailChangeService $emailChange */
		$emailChange = $this->service(EmailChangeService::class, $visitor, $email);

		if (!$visitor->authenticate($password))
		{
			return $this->error(\XF::phrase('your_existing_password_is_not_correct'));
		}

		if (!$emailChange->isValid($changeError))
		{
			return $this->error($changeError);
		}
		if (\XF::isApiCheckingPermissions() && !$emailChange->canChangeEmail($error))
		{
			if (!$error)
			{
				$error = \XF::phrase('your_email_may_not_be_changed_at_this_time');
			}
			return $this->error($error);
		}

		$emailChange->save();

		return $this->apiSuccess([
			'confirmation_required' => $emailChange->getConfirmationRequired(),
		]);
	}

	/**
	 * @api-desc Updates the current user's password
	 *
	 * @api-in str $current_password <req>
	 * @api-in str $new_password <req>
	 *
	 * @api-out true $success
	 */
	public function actionPostPassword()
	{
		$visitor = \XF::visitor();
		$auth = $visitor->Auth->getAuthenticationHandler();
		if (!$auth)
		{
			return $this->noPermission();
		}

		$this->assertRequiredApiInput(['current_password', 'new_password']);

		$currentPassword = $this->filter('current_password', 'str');
		$newPassword = $this->filter('new_password', 'str');

		if (!$visitor->authenticate($currentPassword))
		{
			return $this->error(\XF::phrase('your_existing_password_is_not_correct'));
		}

		/** @var PasswordChangeService $passwordChange */
		$passwordChange = $this->service(PasswordChangeService::class, $visitor, $newPassword);
		$passwordChange->save();

		return $this->apiSuccess();
	}

	/**
	 * @api-desc Updates the current user's avatar
	 *
	 * @api-see XF\Api\ControllerPlugin\User::actionUpdateAvatar()
	 */
	public function actionPostAvatar()
	{
		$visitor = \XF::visitor();
		if (\XF::isApiCheckingPermissions() && !$visitor->canUploadAvatar())
		{
			return $this->noPermission();
		}

		/** @var UserPlugin $userPlugin */
		$userPlugin = $this->plugin(UserPlugin::class);
		return $userPlugin->actionUpdateAvatar($visitor);
	}

	/**
	 * @api-desc Deletes the current user's avatar
	 *
	 * @api-see XF\Api\ControllerPlugin\User::actionDeleteAvatar()
	 */
	public function actionDeleteAvatar()
	{
		$visitor = \XF::visitor();
		if (\XF::isApiCheckingPermissions() && !$visitor->canUploadAvatar())
		{
			return $this->noPermission();
		}

		/** @var UserPlugin $userPlugin */
		$userPlugin = $this->plugin(UserPlugin::class);
		return $userPlugin->actionDeleteAvatar($visitor);
	}
}
