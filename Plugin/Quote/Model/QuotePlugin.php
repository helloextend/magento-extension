<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Plugin\Quote\Model;

use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Model\WarrantyRelation;
use Magento\Checkout\Model\Session;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote\Item;

class QuotePlugin
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var WarrantyRelation
     */
    protected $warrantyRelation;

    const QUOTE_LAST_ADDED_PRODUCT = 'ex_quote_last_added_product';

    /**
     * @param Session $checkoutSession
     * @param Registry $registry
     */
    public function __construct(
        Session  $checkoutSession,
        Registry $registry,
        WarrantyRelation $warrantyRelation
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->registry = $registry;
        $this->warrantyRelation = $warrantyRelation;
    }

    public function beforeAddProduct($subject, $product, $request)
    {
        if ($request->hasWarranty() && $product->getTypeId() !== Type::TYPE_CODE) {
            $this->registry->unregister(self::QUOTE_LAST_ADDED_PRODUCT);
            $this->registry->register(self::QUOTE_LAST_ADDED_PRODUCT, $product);
        } elseif (
            $product->getTypeId() === Type::TYPE_CODE
            && $request->hasProduct()
            && !$request->hasData(TYPE::SECONDARY_SKU)
            && $lastAddedProduct = $this->registry->registry(self::QUOTE_LAST_ADDED_PRODUCT)
        ) {
            $secondarySku = $this->getSecondarySkuByProduct($lastAddedProduct, $subject);
            if ($secondarySku) {
                $product->addCustomOption(Type::SECONDARY_SKU, $secondarySku);
            }
        }
    }

    /**
     * @return \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * @param $product
     * @return string|null
     */
    public function getSecondarySkuByProduct($product,$quote)
    {
        $relatedQuoteItem = $quote->getItemByProduct($product);
        return $this->warrantyRelation->getRelationQuoteItemSku($relatedQuoteItem);

    }
}