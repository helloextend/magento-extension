<?php
/**
 * @author     Guidance Magento Team <magento@guidance.com>
 * @copyright  Copyright (c) 2022 Guidance Solutions (http://www.guidance.com)
 */

namespace Extend\Warranty\Model\RelationProcessor;

use Extend\Warranty\Model\RelationProcessorInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class ConfigurableProcessor extends DefaultProcessor implements RelationProcessorInterface
{
    /**
     * For configurable we should return Child sku
     * so getting it not via getData but via getSku logic
     *
     * @param CartItemInterface $quoteItem
     * @return string
     */
    public function getOfferQuoteItemSku($quoteItem): string
    {
        return $quoteItem->getProduct()->getSku();
    }

    /**
     * For configurable order item logic is different so
     * orderItem->getSku return correct sku for offers on
     * order view page
     *
     * @param OrderItemInterface $quoteItem
     * @return string
     */
    public function getOfferOrderItemSku($orderItem):string
    {
        return $orderItem->getSku();
    }
}