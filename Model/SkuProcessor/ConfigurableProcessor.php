<?php
/**
 * @author     Guidance Magento Team <magento@guidance.com>
 * @copyright  Copyright (c) 2022 Guidance Solutions (http://www.guidance.com)
 */

namespace Extend\Warranty\Model\SkuProcessor;

use Extend\Warranty\Model\SkuProcessorInterface;

class ConfigurableProcessor extends DefaultProcessor implements SkuProcessorInterface
{
    /**
     * For configurable we should return Child sku
     * so getting it not via getData but via getSku logic
     *
     * @param $quoteItem
     * @return string
     */
    public function getRelationQuoteItemSku($quoteItem): string
    {
        return $quoteItem->getProduct()->getSku();
    }

    /**
     * For configurable we should return Child sku
     * so getting it not via getData but via getSku logic
     *
     * @param $quoteItem
     * @return string
     */
    public function getOfferQuoteItemSku($quoteItem): string
    {
        return $quoteItem->getProduct()->getSku();
    }
}