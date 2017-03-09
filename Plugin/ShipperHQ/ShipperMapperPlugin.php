<?php

namespace Swarming\SubscribePro\Plugin\ShipperHQ;

use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;
use Swarming\SubscribePro\Api\Data\ProductInterface as PlatformProductInterface;

class ShipperMapperPlugin
{
    public function aroundPopulateAttributes(\ShipperHQ\Shipper\Model\Carrier\Processor\ShipperMapper $mapper, callable $proceed, $reqdAttributeNames, $item)
    {
        $attributes = $proceed($reqdAttributeNames, $item);

        if ($this->determineIfSubscription($item)) {
            $shippingGroup = '';
            $shippingGroupSet = false;

            $recurringShippingCode = 'SUBSCRIBEPRO_RECURRING';

            if (is_array($attributes)) {
                foreach($attributes as $key => $attribute) {
                    if (isset($attribute['name']) && $attribute['name'] === 'shipperhq_shipping_group') {
                        $shippingGroup = $attribute['value'] . '#' . $recurringShippingCode;
                        $shippingGroupSet = true;
                        $shippingKey = $key;
                    }
                }
                if (!$shippingGroupSet) {
                    $attributes[] = ['name' => 'shipperhq_shipping_group', 'value' => $recurringShippingCode];
                } else {
                    $attributes[$shippingKey] = ['name' => 'shipperhq_shipping_group', 'value' => $shippingGroup];
                }
            }
        }
        return $attributes;
    }

    private function determineIfSubscription($item)
    {
        $buyRequest = $item->getOptionByCode('info_buyRequest');
        if (!$buyRequest) {
            return false;
        }
        $buyRequest = unserialize($buyRequest->getValue());

        if (!isset($buyRequest[OptionProcessor::KEY_SUBSCRIPTION_OPTION])) {
            return false;
        }

        $subscriptionOptions = $buyRequest[OptionProcessor::KEY_SUBSCRIPTION_OPTION];

        if (!isset($subscriptionOptions[SubscriptionOptionInterface::OPTION])) {
            return false;
        }

        return $subscriptionOptions[SubscriptionOptionInterface::OPTION] == PlatformProductInterface::SO_SUBSCRIPTION;
    }
}

