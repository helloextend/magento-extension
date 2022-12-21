<?php
/**
 * @author     Guidance Magento Team <magento@guidance.com>
 * @copyright  Copyright (c) 2022 Guidance Solutions (http://www.guidance.com)
 */

namespace Extend\Warranty\Model\SkuProcessor;

use Extend\Warranty\Model\SkuProcessorInterface;

class DefaultProcessor implements SkuProcessorInterface
{
    /**
     * @param $quoteItem
     * @return string
     */
    public function getRelationQuoteItemSku($quoteItem): string
    {
        return $quoteItem->getProduct()->getData('sku');
    }

    /**
     * @param $quoteItem
     * @return string
     */
    public function getOfferQuoteItemSku($quoteItem): string
    {
        return $quoteItem->getProduct()->getData('sku');
    }
}