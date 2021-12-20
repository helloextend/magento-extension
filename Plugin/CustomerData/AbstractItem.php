<?php

namespace Extend\Warranty\Plugin\CustomerData;

use Magento\Checkout\CustomerData\AbstractItem as SuperAbstractItem;
use Magento\Quote\Model\Quote\Item;

class AbstractItem
{
    public function afterGetItemData(SuperAbstractItem $subject, $result, Item $item)
    {
        $result['product_image']['isWarranty'] = $result['product_type'] === 'warranty';
        return $result;
    }
}