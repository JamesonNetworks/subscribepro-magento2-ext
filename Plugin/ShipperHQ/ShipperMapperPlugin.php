<?php

namespace Swarming\SubscribePro\Plugin\ShippingHQ;

class ShipperMapperPlugin
{
    public function aroundPopulateAttributes(\ShipperHQ\Shipper\Model\Carrier\Processor\ShipperMapper $mapper, callable $proceed, ...$args)
    {
        $returnValue = $proceed(...$args);
        if (is_array($returnValue)) {
            $returnValue[] = ['name' => 'shipperhq_shipping_group', 'value' => true];
        }
        return $returnValue;
    }
}


