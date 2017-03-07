<?php

namespace Swarming\SubscribePro\Plugin\ShippingMQ;

class ShipperMapperPlugin
{
    public function aroundPopulateAttributes(ShipperHQ\Shipper\Model\Carrier\Processor\ShipperMapper $mapper, callable $proceed)
    {
        $returnValue = $proceed();
        $returnValue['shipperhq_shipping_group'] = true;
        return $returnValue;
    }
}
