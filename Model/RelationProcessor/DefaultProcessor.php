<?php
/**
 * @author     Guidance Magento Team <magento@guidance.com>
 * @copyright  Copyright (c) 2022 Guidance Solutions (http://www.guidance.com)
 */

namespace Extend\Warranty\Model\RelationProcessor;

use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Model\RelationProcessorInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class DefaultProcessor implements RelationProcessorInterface
{
    /**
     * @param CartItemInterface|OrderItemInterface $quoteItem
     * @return string
     */
    public function getRelationQuoteItemSku($quoteItem): string
    {
        return $quoteItem->getProduct()->getData('sku');
    }

    /**
     * @param CartItemInterface|OrderItemInterface $quoteItem
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
        $secondarySku = $warrantyItem->getOptionByCode(Type::SECONDARY_SKU);
        if ($secondarySku
            && $secondarySku->getValue()
            && $associatedProductSku->getValue() !== $secondarySku->getValue()
        ) {
            return false;
        }

        $relatedSkus = $associatedProductSku->getValue();

        $itemSku = $this->getRelationQuoteItemSku($item);

        return $itemSku == $relatedSkus;
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

        $secondarySku = $warrantyItem->getProductOptionByCode(Type::SECONDARY_SKU);
        if ($secondarySku
            && $relatedSku !== $secondarySku
        ) {
            return false;
        }

        $itemSku = $this->getRelationQuoteItemSku($orderItem);

        return $itemSku == $relatedSku;
    }

    public function isWarrantyDataRelatedToQuoteItem($warrantyData, $quoteItem): bool
    {
        if (isset($warrantyData['product'])
            && $quoteItem->getProduct()
            && $warrantyData['product'] == $this->getRelationQuoteItemSku($quoteItem)
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
    public function getOfferOrderItemSku($orderItem):string
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
    public function getRelationOrderItemSku($orderItem):string
    {
        return $orderItem->getProduct()->getSku();
    }
}