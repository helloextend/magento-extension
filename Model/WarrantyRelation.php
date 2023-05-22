<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model;

use Extend\Warranty\Model\Product\Type;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Api\Data\OrderItemInterface;

class WarrantyRelation
{
    const DEFAULT_SKU_PROCESSOR = 'default';

    /**
     * @var RelationProcessorInterface[]
     */
    private array $relationProcessors = [];

    protected $checkoutSession;

    /**
     * @param RelationProcessorInterface[] $relationProcessors
     */
    public function __construct(
        Session $checkoutSession,
                $relationProcessors = []
    )
    {
        $this->relationProcessors = $relationProcessors;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param CartItemInterface $warrantyItem
     * @param CartItemInterface $quoteItem
     * @param $checkWithChildren
     * @return bool
     */
    public function isWarrantyRelatedToQuoteItem(
        CartItemInterface $warrantyItem,
        CartItemInterface $quoteItem,
                          $checkWithChildren = false
    ): bool
    {
        return $this->getProcessor($quoteItem->getProductType())
            ->isWarrantyRelatedToQuoteItem(
                $warrantyItem,
                $quoteItem,
                $checkWithChildren
            );
    }

    /**
     * @param OrderItemInterface $warrantyItem
     * @param OrderItemInterface $orderItem
     * @param $checkWithChildren
     * @return bool
     */
    public function isWarrantyRelatedToOrderItem(
        OrderItemInterface $warrantyItem,
        OrderItemInterface $orderItem,
                           $checkWithChildren = false
    ): bool
    {
        return $this->getProcessor($orderItem->getProductType())
            ->isWarrantyRelatedToOrderItem(
                $warrantyItem,
                $orderItem,
                $checkWithChildren
            );
    }

    /**
     * Return sku for warrantable product
     * which should be used for relation between warranty and warrantable items
     *
     * @param Item $quoteItem
     * @return string
     */
    public function getRelationQuoteItemSku($quoteItem): string
    {
        return $this->getProcessor($quoteItem->getProductType())
            ->getRelationQuoteItemSku($quoteItem);;
    }

    /**
     * @param $relationSku
     * @return CartItemInterface
     */
    public function getWarrantyByRelationSku($relationSku)
    {
        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (\Exception $e) {
            //log that quote is not loaded
            return null;
        }

        /** @var CartItemInterface $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getProductType() == Type::TYPE_CODE) {
                $associatedProductSku = $quoteItem->getOptionByCode(Type::ASSOCIATED_PRODUCT);
                if ($associatedProductSku && $associatedProductSku->getValue() == $relationSku) {
                    return $quoteItem;
                }
            }
        }
        return null;
    }

    /**
     * Return product sku which is used to request offers from Extend
     *
     * @param CartItemInterface $item
     * @return string
     */
    public function getOfferQuoteItemSku($item): string
    {
        return $this->getProcessor($item->getProductType())
            ->getOfferQuoteItemSku($item);
    }

    /**
     * Return product sku which is used to request offers from Extend
     *
     * @param CartItemInterface $item
     * @return string
     */
    public function getOfferOrderItemSku($item): string
    {
        return $this->getProcessor($item->getProductType())
            ->getOfferOrderItemSku($item);
    }

    /**
     * Return product sku which is used to keep relation with warrantable product
     *
     * @param CartItemInterface $item
     * @return string
     */
    public function getRelationOrderItemSku($item): string
    {
        return $this->getProcessor($item->getProductType())
            ->getRelationOrderItemSku($item);
    }

    /**
     * @param $warrantyData
     * @return CartItemInterface|null
     */
    public function getRelatedQuoteItemByWarrantyData($warrantyData)
    {
        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (\Exception $e) {
            //log that quote is not loaded
            return null;
        }
        /** @var CartItemInterface $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            $relatedProcessor = $this->getProcessor($quoteItem->getProductType());
            if ($relatedProcessor->isWarrantyDataRelatedToQuoteItem($warrantyData, $quoteItem)) {
                return $quoteItem;
            }
        }
        return null;
    }

    /**
     * Checks if quote item has related warranty in cart
     * @param CartItemInterface $quoteItem
     * @return bool
     */
    public function quoteItemHasWarranty(CartItemInterface $quoteItem): bool
    {
        $hasWarranty = false;
        $quote = $quoteItem->getQuote();
        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            if ($item->getProductType() === Type::TYPE_CODE
                && $this->isWarrantyRelatedToQuoteItem($item, $quoteItem)
            ) {
                $hasWarranty = true;
            }
        }

        return $hasWarranty;
    }

    /**
     * Checks if order item has related warranty in order items
     * @param OrderItemInterface $orderItem
     * @return OrderItemInterface[]
     */
    public function getOrderItemWarranty(OrderItemInterface $orderItem)
    {
        $order = $orderItem->getOrder();
        $items = $order->getAllVisibleItems();
        $warrantyItems = [];
        foreach ($items as $item) {
            if ($item->getProductType() === Type::TYPE_CODE
                && $this->isWarrantyRelatedToOrderItem($item, $orderItem)
            ) {
                $warrantyItems[] = $item;
            }
        }
        return $warrantyItems;
    }

    /**
     * @param $quoteItem
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getWarrantiesByQuoteItem($quoteItem)
    {
        $quote = $this->checkoutSession->getQuote();

        $warranties = [];
        foreach ($quote->getAllItems() as $item) {
            $relatedProcessor = $this->getProcessor($quoteItem->getProductType());

            if ($item->getProductType() !== Type::TYPE_CODE) {
                continue;
            }
            if ($relatedProcessor->isWarrantyRelatedToQuoteItem($item, $quoteItem)) {
                $warranties[] = $item;
            }
        }
        return $warranties;

    }

    /**
     * @param OrderItemInterface $warrantyOrderItem
     * @return OrderItemInterface|null
     */
    public function getAssociatedOrderItem($warrantyOrderItem)
    {
        $order = $warrantyOrderItem->getOrder();

        foreach ($order->getAllItems() as $item) {
            if ($this->isWarrantyRelatedToOrderItem($warrantyOrderItem, $item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Get Relation processor by product type
     *
     * @param $productType
     * @return RelationProcessorInterface
     */
    private function getProcessor($productType)
    {
        $processorType = self::DEFAULT_SKU_PROCESSOR;

        if (isset($this->relationProcessors[$productType])) {
            $processorType = $productType;
        }
        return $this->relationProcessors[$processorType];
    }
}