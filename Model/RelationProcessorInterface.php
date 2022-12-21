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

interface RelationProcessorInterface
{
    public function isWarrantyRelatedToQuoteItem($warrantyItem,$quoteItem,$checkWithChildren = false):bool;

    /**
     * Return related quote item for warranty data
     * It need in add warranty requests from mini cart or checkout cart
     *
     * @param $warrantyData
     * @param $quoteItem
     * @return bool
     */
    public function isWarrantyDataRelatedToQuoteItem($warrantyData,$quoteItem):bool;

    /**
     * Get Product SKU which is used to relate warrantable
     * and warranty quote item
     *
     * @param $quoteItem
     * @return string
     */
    public function getRelationQuoteItemSku($quoteItem): string;

    /**
     * Get Product SKU to request offers on
     * checkout cart and mini cart
     *
     * @param $quoteItem
     * @return string
     */
    public function getOfferQuoteItemSku($quoteItem): string;
}