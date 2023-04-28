<?php
/**
 * @author     Guidance Magento Team <magento@guidance.com>
 * @copyright  Copyright (c) 2023 Guidance Solutions (http://www.guidance.com)
 */

namespace Extend\Warranty\Model\Api\Request\LineItem;

use Extend\Warranty\Model\Api\Request\LineItemBuilder;

class LeadLineItemBuilder extends  AbstractLineItemBuilder
{
    public function preparePayload($item)
    {
        if (!$this->validate($item)) {
            return [];
        }

        $productSku = $this->warrantyRelation->getOfferOrderItemSku($orderItem);
        $product = $this->prepareProductPayload($productSku);
        $product['purchasePrice'] = $purchasePrice;

        $lineItem = [
            'status' => $this->getStatus(),
            'discountAmount' => $discountAmount,
            'taxCost' => $taxCost,
            'product' => $product
        ];

        return $lineItem;
    }
}