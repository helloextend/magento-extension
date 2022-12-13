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

    public function getRelationProductSku($product): string;

    public function getRelationQuoteItemSku($quoteItem): string;
}