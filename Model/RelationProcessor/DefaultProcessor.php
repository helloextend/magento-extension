<?php
/**
 * @author     Guidance Magento Team <magento@guidance.com>
 * @copyright  Copyright (c) 2022 Guidance Solutions (http://www.guidance.com)
 */

namespace Extend\Warranty\Model\RelationProcessor;

use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Model\RelationProcessorInterface;

class DefaultProcessor implements RelationProcessorInterface
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

    public function isWarrantyRelatedToQuoteItem($warrantyItem, $quoteItem, $checkWithChildren = false): bool
    {
        $associatedProductSku = $warrantyItem->getOptionByCode(Type::ASSOCIATED_PRODUCT);
        $relatedSkus = [$associatedProductSku->getValue()];

        $itemSku = $this->getRelationQuoteItemSku($quoteItem);

        return in_array($itemSku, $relatedSkus);
    }

    public function isWarrantyDataRelatedToQuoteItem($warrantyData, $quoteItem): bool
    {
        if(isset($warrantyData['product'])
            && $quoteItem->getProduct()
            && $quoteItem->getProduct()->getData('sku')
            && $warrantyData['product'] == $quoteItem->getProduct()->getData('sku')
        ){
            return true;
        }
        return false;
    }
}