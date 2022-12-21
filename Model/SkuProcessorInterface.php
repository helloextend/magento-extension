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

interface SkuProcessorInterface
{
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