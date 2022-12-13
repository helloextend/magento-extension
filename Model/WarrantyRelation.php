<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model;

use Extend\Warranty\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item;

class WarrantyRelation
{
    const DEFAULT_SKU_PROCESSOR = 'default';
    /**
     * @var SkuProcessorInterface[]
     */
    private $skuProcessors;

    /**
     * @param $skuProcessors
     */
    public function __construct($skuProcessors = [])
    {
        $this->skuProcessors = $skuProcessors;
    }

    /**
     * @param Item $warrantyItem
     * @param Item $quoteItem
     * @param $checkWithChildren
     * @return bool
     */
    public function isWarrantyRelatedToQuoteItem(Item $warrantyItem, Item $quoteItem, $checkWithChildren = false): bool
    {
        $associatedProductSku = $warrantyItem->getOptionByCode(Type::ASSOCIATED_PRODUCT);

        $relatedSkus = [$associatedProductSku->getValue()];

        $itemSku = $this->getComplexQuoteItemSku($quoteItem);
        $skuCheck = in_array($itemSku, $relatedSkus);

        return $skuCheck;
    }

    /**
     * Return sku for warrantable product
     *
     * @param Item $quoteItem
     * @return string
     */
    public function getComplexQuoteItemSku($quoteItem): string
    {
        if (isset($this->skuProcessors[$quoteItem->getProductType()])) {
            $relationSku = $this->skuProcessors[$quoteItem->getProductType()]->getRelationQuoteItemSku($quoteItem);
        } else {
            $relationSku =$this->skuProcessors[self::DEFAULT_SKU_PROCESSOR]->getRelationQuoteItemSku($quoteItem);
        }

        return $relationSku;
    }
}