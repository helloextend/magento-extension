<?php
/**
 * @author     Guidance Magento Team <magento@guidance.com>
 * @copyright  Copyright (c) 2022 Guidance Solutions (http://www.guidance.com)
 */

namespace Extend\Warranty\Model\SkuProcessor;

use Extend\Warranty\Model\SkuProcessorInterface;

class DefaultProcessor implements SkuProcessorInterface
{
    public function getRelationProductSku($product): string
    {
        return $product->getData('sku');
    }

    public function getRelationQuoteItemSku($quoteItem): string
    {
        return self::getRelationProductSku($quoteItem->getProduct());
    }
}