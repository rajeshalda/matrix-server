<?php

namespace XF\Entity;

use XF\Api\Result\EntityResult;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Payment\AbstractProvider;
use XF\Phrase;
use XF\Repository\UserUpgradeRepository;
use XF\Service\User\UserGroupChangeService;

/**
 * COLUMNS
 * @property int|null $user_upgrade_id
 * @property string $title
 * @property string $description
 * @property int $display_order
 * @property array $extra_group_ids
 * @property bool $recurring
 * @property float $cost_amount
 * @property string $cost_currency
 * @property int $length_amount
 * @property string $length_unit
 * @property array $disabled_upgrade_ids
 * @property bool $can_purchase
 * @property array $payment_profile_ids
 *
 * GETTERS
 * @property-read Phrase|string $cost_phrase
 * @property-read mixed $cost_phrase_for_purchase_request
 * @property-read string $purchasable_type_id
 *
 * RELATIONS
 * @property-read \XF\Mvc\Entity\AbstractCollection<\XF\Entity\UserUpgradeActive> $Active
 */
class UserUpgrade extends Entity
{
	public function canPurchase()
	{
		$visitor = \XF::visitor();
		return ($this->can_purchase && !isset($this->Active[$visitor->user_id]));
	}

	/**
	 * @return Phrase|string
	 */
	public function getCostPhrase()
	{
		return $this->getUserUpgradeRepo()->getCostPhraseForUserUpgrade($this);
	}

	public function getCostPhraseForPurchaseRequest(PurchaseRequest $purchaseRequest)
	{
		return $this->getUserUpgradeRepo()->getCostPhraseForUserUpgrade(
			$this,
			$purchaseRequest->cost_amount,
			$purchaseRequest->cost_currency
		);
	}

	/**
	 * @return string
	 */
	public function getPurchasableTypeId()
	{
		return 'user_upgrade';
	}

	protected function _preSave()
	{
		if ($this->isChanged(['recurring', 'length_amount', 'length_unit', 'cost_currency']))
		{
			/** @var PaymentProfile[] $profiles */
			$profiles = $this->_em->findByIds(PaymentProfile::class, $this->payment_profile_ids);

			if ($this->isChanged(['recurring', 'length_amount', 'length_unit']) && $this->recurring)
			{
				$invalidRecurring = [];
				$invalidLength = [];

				foreach ($profiles AS $profile)
				{
					$supportsRecurring = $profile->supportsRecurring($this->length_unit, $this->length_amount, $result);
					if (!$supportsRecurring)
					{
						if ($result === AbstractProvider::ERR_NO_RECURRING)
						{
							$invalidRecurring[] = $profile->Provider->getTitle();
						}
						else if ($result === AbstractProvider::ERR_INVALID_RECURRENCE)
						{
							$invalidLength[] = $profile->Provider->getTitle();
						}
					}
				}

				if ($invalidRecurring)
				{
					$invalidRecurring = implode(', ', array_unique($invalidRecurring));
					$this->error(\XF::phrase('following_payment_providers_do_not_support_recurring_payments', ['invalidRecurring' => $invalidRecurring]), 'recurring');
				}

				if ($invalidLength)
				{
					$invalidLength = implode(', ', array_unique($invalidLength));
					$this->error(\XF::phrase('following_payment_providers_support_recurring_payments_but_invalid_length', ['invalidLength' => $invalidLength]), 'recurring');
				}
			}

			if ($this->isChanged('cost_currency'))
			{
				$invalidCurrency = [];

				foreach ($profiles AS $profile)
				{
					if (!$profile->verifyCurrency($this->cost_currency))
					{
						$invalidCurrency[] = $profile->Provider->getTitle();
					}
				}

				if ($invalidCurrency)
				{
					$invalidCurrency = implode(', ', array_unique($invalidCurrency));
					$this->error(\XF::phrase('following_payment_providers_do_not_support_x_as_valid_currency', ['currency' => $this->cost_currency, 'invalidCurrency' => $invalidCurrency]), 'currency_code');
				}
			}
		}

		if (!$this->length_amount || !$this->length_unit)
		{
			$this->length_amount = 0;
			$this->length_unit = '';
		}
	}

	protected function _postSave()
	{
		$this->rebuildUpgradeCount();
	}

	protected function _postDelete()
	{
		$this->getUserGroupChangeService()->removeUserGroupChangeLogByKey("userUpgrade-$this->user_upgrade_id");
		$this->rebuildUpgradeCount();
	}

	protected function rebuildUpgradeCount()
	{
		\XF::runOnce('upgradeCountRebuild', function ()
		{
			$this->getUserUpgradeRepo()->rebuildUpgradeCount();
		});
	}

	protected function setupApiResultData(EntityResult $result, $verbosity = self::VERBOSITY_NORMAL, array $options = []): void
	{
		$result->cost_phrase = $this->cost_phrase;
	}

	public static function getStructure(Structure $structure)
	{
		$structure->table = 'xf_user_upgrade';
		$structure->shortName = 'XF:UserUpgrade';
		$structure->contentType = 'user_upgrade';
		$structure->primaryKey = 'user_upgrade_id';
		$structure->columns = [
			'user_upgrade_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true, 'api' => true],
			'title' => ['type' => self::STR, 'maxLength' => 50,
				'required' => 'please_enter_valid_title',
				'api' => true,
			],
			'description' => ['type' => self::STR, 'default' => '', 'api' => true],
			'display_order' => ['type' => self::UINT, 'default' => 0, 'api' => true],
			'extra_group_ids' => ['type' => self::LIST_COMMA, 'default' => [],
				'list' => ['type' => 'posint', 'unique' => true, 'sort' => SORT_NUMERIC],
				'api' => true,
			],
			'recurring' => ['type' => self::BOOL, 'default' => false, 'api' => true],
			'cost_amount' => ['type' => self::FLOAT, 'required' => true, 'min' => 0.01, 'api' => true],
			'cost_currency' => ['type' => self::STR, 'required' => true, 'api' => true],
			'length_amount' => ['type' => self::UINT, 'max' => 255, 'required' => true, 'api' => true],
			'length_unit' => ['type' => self::STR, 'default' => '',
				'allowedValues' => ['day', 'month', 'year', ''],
				'api' => true,
			],
			'disabled_upgrade_ids' => ['type' => self::LIST_COMMA, 'default' => [],
				'list' => ['type' => 'posint', 'unique' => true, 'sort' => SORT_NUMERIC],
				'api' => true,
			],
			'can_purchase' => ['type' => self::BOOL, 'default' => true, 'api' => true],
			'payment_profile_ids' => ['type' => self::LIST_COMMA,
				'required' => 'please_select_at_least_one_payment_profile',
				'list' => ['type' => 'posint', 'unique' => true, 'sort' => SORT_NUMERIC],
				'api' => true,
			],
		];
		$structure->getters = [
			'cost_phrase' => true,
			'cost_phrase_for_purchase_request' => true,
			'purchasable_type_id' => true,
		];
		$structure->relations = [
			'Active' => [
				'entity' => 'XF:UserUpgradeActive',
				'type' => self::TO_MANY,
				'conditions' => 'user_upgrade_id',
				'key' => 'user_id',
			],
		];
		$structure->behaviors = [
			'XF:Webhook' => [],
		];

		return $structure;
	}

	/**
	 * @return UserUpgradeRepository
	 */
	protected function getUserUpgradeRepo()
	{
		return $this->repository(UserUpgradeRepository::class);
	}

	/**
	 * @return UserGroupChangeService
	 */
	protected function getUserGroupChangeService()
	{
		return $this->app()->service(UserGroupChangeService::class);
	}
}
