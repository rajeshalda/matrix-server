<?php

namespace XF\Repository;

use XF\Entity\PaymentProfile;
use XF\Entity\PaymentProviderLog;
use XF\Finder\PaymentProfileFinder;
use XF\Finder\PaymentProviderFinder;
use XF\Finder\PaymentProviderLogFinder;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

use function strlen;

class PaymentRepository extends Repository
{
	/**
	 * @return Finder
	 */
	public function findPaymentProvidersForList()
	{
		return $this->finder(PaymentProviderFinder::class)
			->order('provider_id');
	}

	/**
	 * @return Finder
	 */
	public function findActivePaymentProviders()
	{
		return $this->findPaymentProvidersForList()
			->whereAddOnActive();
	}

	/**
	 * @return Finder
	 */
	public function findPaymentProfilesForList()
	{
		return $this->finder(PaymentProfileFinder::class)
			->with('Provider', true)
			->where('active', true)
			->whereAddOnActive([
				'relation' => 'Provider.AddOn',
				'column' => 'Provider.addon_id',
			])
			->order('title');
	}

	public function getPaymentProfileTitlePairs()
	{
		$pairs = [];

		foreach ($this->findPaymentProfilesForList()->fetch() AS $profile)
		{
			/** @var PaymentProfile $profile */
			if (!$profile->active)
			{
				// extra sanity check to only include handlers we know we can use
				continue;
			}

			$pairs[$profile->payment_profile_id] = $profile->display_title ?: $profile->title;
		}

		return $pairs;
	}

	public function getPaymentProfileOptionsData($includeEmpty = true)
	{
		$choices = [];
		if ($includeEmpty)
		{
			$choices = [
				0 => ['value' => 0, 'label' => \XF::phrase('(choose_payment_method)')],
			];
		}

		$choices += $this->getPaymentProfileTitlePairs();

		return $choices;
	}

	/**
	 * @template T of \XF\Payment\AbstractProvider
	 *
	 * @param class-string<T> $providerClass
	 *
	 * @return T|null
	 */
	public function getPaymentProviderHandler(
		string $providerId,
		string $providerClass
	)
	{
		$class = \XF::stringToClass($providerClass, '%s\Payment\%s');
		if (!class_exists($class))
		{
			return null;
		}

		$class = \XF::extendClass($class);
		return new $class($providerId);
	}

	public function getPaymentProviderCacheData()
	{
		/** @var PaymentProfile[]|AbstractCollection $paymentProfiles */
		$paymentProfiles = $this->findPaymentProfilesForList()->fetch();

		$cache = [];

		foreach ($paymentProfiles AS $paymentProfile)
		{
			$paymentProvider = $paymentProfile->Provider;
			$paymentProviderId = $paymentProvider->provider_id;

			$cache[$paymentProviderId] = [
				'provider_id' => $paymentProvider->provider_id,
				'provider_class' => $paymentProvider->provider_class,
				'addon_id' => $paymentProvider->addon_id,
			];
		}

		return $cache;
	}

	public function rebuildPaymentProviderCache()
	{
		$cache = $this->getPaymentProviderCacheData();
		\XF::registry()->set('paymentProvider', $cache);
		return $cache;
	}

	/**
	 * @param $transactionId
	 *
	 * @return Finder
	 */
	public function findLogsByTransactionId($transactionId, $logType = ['payment', 'cancel'])
	{
		return $this->finder(PaymentProviderLogFinder::class)
			->where('transaction_id', $transactionId)
			->where('log_type', $logType)
			->setDefaultOrder('log_date');
	}

	public function findLogsByTransactionIdForProvider($transactionId, $providerId, $logType = ['payment', 'cancel'])
	{
		return $this->findLogsByTransactionId($transactionId, $logType)
			->where('provider_id', $providerId);
	}

	public function logCallback($requestKey, $providerId, $txnId, $logType, $logMessage, array $logDetails, $subId = null)
	{
		/** @var PaymentProviderLog $providerLog */
		$providerLog = $this->em->create(PaymentProviderLog::class);

		if ($requestKey && strlen($requestKey) > 32)
		{
			$requestKey = substr($requestKey, 0, 29) . '...';
		}

		$providerLog->purchase_request_key = $requestKey;
		$providerLog->provider_id = $providerId;
		$providerLog->transaction_id = $txnId;
		$providerLog->log_type = $logType;
		$providerLog->log_message = $logMessage;
		$providerLog->log_details = $logDetails;
		$providerLog->subscriber_id = $subId;
		$providerLog->log_date = time();

		return $providerLog->save();
	}
}
