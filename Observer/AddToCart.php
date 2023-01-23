<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Observer;

use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Model\WarrantyRelation;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;

class AddToCart implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Registry
     */
    protected $registry;

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

    /**
     * This method saves product to registry
     * so when warranty added we have product
     * in registry to refer to product in warranty
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $info = $observer->getInfo();
        /** @var ProductInterface $product */
        $product = $observer->getProduct();

        if (isset($info['warranty']) && $product->getTypeId() !== Type::TYPE_CODE) {
            $this->registry->unregister(self::QUOTE_LAST_ADDED_PRODUCT);
            $this->registry->register(self::QUOTE_LAST_ADDED_PRODUCT, $product);
        } elseif (
            $product->getTypeId() === Type::TYPE_CODE
            && isset($info['product'])
            && !isset($info[TYPE::SECONDARY_SKU])
            && $lastAddedProduct = $this->registry->registry(self::QUOTE_LAST_ADDED_PRODUCT)
        ) {
            $secondarySku = $this->getSecondarySkuByProduct($lastAddedProduct);
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
    public function getSecondarySkuByProduct($product)
    {
        try {
            $quote = $this->getQuote();
        } catch (\Exception $e) {
            return null;
        }
        $relatedQuoteItem = $quote->getItemByProduct($product);
        return $this->warrantyRelation->getRelationQuoteItemSku($relatedQuoteItem);

    }
}