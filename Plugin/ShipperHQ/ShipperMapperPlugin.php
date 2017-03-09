<?php

namespace Swarming\SubscribePro\Plugin\ShippingHQ;

class ShipperMapperPlugin
{
    public function aroundPopulateAttributes(\ShipperHQ\Shipper\Model\Carrier\Processor\ShipperMapper $mapper, callable $proceed, $reqdAttributeNames, $item)
    {
        log(get_class($item));
        log(json_encode($item));
        $attributes = $proceed($reqdAttributeNames, $item);
        if (is_array($attributes)) {
            foreach($attributes as $attribute) {
                if (isset($attribute['name']) && $attribute['name'] === 'shipperhq_shipping_group') {
                    $attribute['value'] .= '#SUBSCRIBEPRO_RECURRING';
                }
            }
        }
        return $attributes;
    }
}
