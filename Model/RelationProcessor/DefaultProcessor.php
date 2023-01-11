<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\RelationProcessor;

use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Model\RelationProcessorInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class DefaultProcessor implements RelationProcessorInterface
{
    /**
     * @param CartItemInterface $quoteItem
     * @return string
     */
    public function getRelationQuoteItemSku($quoteItem): string
    {
        return $quoteItem->getProduct()->getSku();
    }

    /**
     * @param CartItemInterface $quoteItem
     * @return string
     */
    public function getOfferQuoteItemSku($quoteItem): string
    {
        return $quoteItem->getProduct()->getData('sku');
    }

    public function isWarrantyRelatedToQuoteItem($warrantyItem, $item, $checkWithChildren = false): bool
    {
        $associatedProductSku = $warrantyItem->getOptionByCode(Type::ASSOCIATED_PRODUCT);

        /**
         * In default Relation Secondary SKU = Associated SKU
         * Secondary SKU is more specific relation so it should be checked in specific processors
         *
         * In default scenario SECONDARY SKU = ASSOCIATED SKU
         */
        $associatedProductSku = $warrantyItem->getOptionByCode(Type::ASSOCIATED_PRODUCT);
        $relatedSku = $associatedProductSku->getValue();

        $relatedSku = $warrantyItem->getOptionByCode(Type::SECONDARY_SKU)
            ? $warrantyItem->getOptionByCode(Type::SECONDARY_SKU)->getValue()
            : $relatedSku;

        $itemSku = $this->getRelationQuoteItemSku($item);

        return $itemSku == $relatedSku;
    }

    /**
     * Same method as for quote item
     *
     * @param OrderItemInterface $warrantyItem
     * @param OrderItemInterface $orderItem
     * @param $checkWithChildren
     * @return bool
     */
    public function isWarrantyRelatedToOrderItem(OrderItemInterface $warrantyItem, OrderItemInterface $orderItem, $checkWithChildren = false): bool
    {
        $relatedSku = $warrantyItem->getProductOptionByCode(Type::ASSOCIATED_PRODUCT);

        $relatedSku = $warrantyItem->getProductOptionByCode(Type::SECONDARY_SKU)
            ? $warrantyItem->getProductOptionByCode(Type::SECONDARY_SKU)
            : $relatedSku;

        $itemSku = $this->getRelationOrderItemSku($orderItem);

        return $itemSku == $relatedSku;
    }

    public function isWarrantyDataRelatedToQuoteItem($warrantyData, $quoteItem): bool
    {
        $relatedQuoteItemSku = $this->getRelationQuoteItemSku($quoteItem);

        if (isset($warrantyData['product'])
            && $quoteItem->getProduct()
            && $warrantyData['product'] == $relatedQuoteItemSku
            && !$quoteItem->getProduct()->getOptions()
        ) {
            return true;
        }

        if (
            isset($warrantyData[Type::SECONDARY_SKU])
            && $warrantyData[Type::SECONDARY_SKU] == $relatedQuoteItemSku
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get Order Item SKU to request offers
     * needed on order view
     *
     * @param OrderItemInterface $quoteItem
     * @return string
     */
    public function getOfferOrderItemSku($orderItem): string
    {
        return $orderItem->getProduct()->getSku();
    }

    /**
     * Get Order Item SKU which is used to relate warrantable
     * and warranty quote item.
     *
     * Needed on order view page
     *
     * @param OrderItemInterface $quoteItem
     * @return string
     */
    public function getRelationOrderItemSku($orderItem): string
    {
        $relationSku = $orderItem->getProduct()->getSku();
        if($orderItem->getProduct()->hasOptions()){
            $relationSku = $orderItem->getSku();
        }
        return $relationSku;
    }
}