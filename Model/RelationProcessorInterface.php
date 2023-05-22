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

use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

interface RelationProcessorInterface
{
    /**
     * @param CartItemInterface $warrantyItem
     * @param CartItemInterface $item
     * @param $checkWithChildren
     * @return bool
     */
    public function isWarrantyRelatedToQuoteItem(CartItemInterface $warrantyItem, CartItemInterface $item, $checkWithChildren = false): bool;

    /**
     * this method need to decide if  order item has warranty and create
     * lead if not
     *
     * @param OrderItemInterface $warrantyItem
     * @param OrderItemInterface $item
     * @param $checkWithChildren
     * @return bool
     */
    public function isWarrantyRelatedToOrderItem(OrderItemInterface $warrantyItem, OrderItemInterface $item, $checkWithChildren = false): bool;

    /**
     * Return related quote item for warranty data from Request
     * It need in add warranty requests from mini cart or checkout cart
     *
     * @param $warrantyData
     * @param $quoteItem
     * @return bool
     */
    public function isWarrantyDataRelatedToQuoteItem($warrantyData, $quoteItem): bool;

    /**
     * Get Product SKU which is used to relate warrantable
     * and warranty quote item
     *
     * @param CartItemInterface $quoteItem
     * @return string
     */
    public function getRelationQuoteItemSku($quoteItem): string;

    /**
     * Get Product SKU to request offers on
     * checkout cart and mini cart
     *
     * @param CartItemInterface $quoteItem
     * @return string
     */
    public function getOfferQuoteItemSku($quoteItem): string;

    /**
     * Get Order Item SKU to request offers
     * needed on order view
     *
     * @param OrderItemInterface $quoteItem
     * @return string
     */
    public function getOfferOrderItemSku($orderItem): string;

    /**
     * Get Order Item SKU which is used to relate warrantable
     * and warranty order item.
     *
     * Needed on order view page
     *
     * @param OrderItemInterface $quoteItem
     * @return string
     */
    public function getRelationOrderItemSku($orderItem): string;
}