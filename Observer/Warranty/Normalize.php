<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */
namespace Extend\Warranty\Observer\Warranty;

/**
 * Class Normalize
 * @package Extend\Warranty\Observer\Warranty
 */
class Normalize implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Extend\Warranty\Model\Normalizer
     */
    protected $_normalizer;

    /**
     * @var \Extend\Warranty\Helper\Api\Data
     */
    protected $_apiHelper;

    /**
     * @var \Extend\Warranty\Helper\Tracking
     */
    protected $_trackingHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\GroupedProduct\Model\Product\Type\Grouped
     */
    protected $_grouped;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * Normalize constructor.
     * @param \Extend\Warranty\Model\Normalizer $normalizer
     * @param \Extend\Warranty\Helper\Api\Data $apiHelper
     * @param \Extend\Warranty\Helper\Tracking $trackingHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\GroupedProduct\Model\Product\Type\Grouped $grouped
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Extend\Warranty\Model\Normalizer $normalizer,
        \Extend\Warranty\Helper\Api\Data $apiHelper,
        \Extend\Warranty\Helper\Tracking $trackingHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\GroupedProduct\Model\Product\Type\Grouped $grouped,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    )
    {
        $this->_normalizer = $normalizer;
        $this->_apiHelper = $apiHelper;
        $this->_trackingHelper = $trackingHelper;
        $this->_checkoutSession = $checkoutSession;
        $this->_grouped = $grouped;
        $this->_productRepository = $productRepository;
    }

    /**
     * @inheritDoc
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_apiHelper->isBalancedCart()) {
            return;
        }
        /** @var \Magento\Checkout\Model\Cart $cart */
        $cart = $observer->getData('cart');
        /* Normalize on quote/cart update */
        if (empty($cart)) {
            try {
                $this->_normalize($this->_checkoutSession->getQuote());
            } catch (\Exception $e) {}
        } else {
            $this->_normalizer->normalize($cart);
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @noinspection PhpDeprecationInspection
     * @noinspection PhpUnusedLocalVariableInspection
     */
    private function _normalize(\Magento\Quote\Model\Quote $quote)
    {
        //split cart items into products and warranties
        $warranties = [];
        $products = [];
        foreach ($quote->getAllItems() as $item) {
            /** @var \Magento\Quote\Model\Quote\Item $item */
            if ($item->getProductType() === \Extend\Warranty\Model\Product\Type::TYPE_CODE) {
                $warranties[$item->getItemId()] = $item;
            } else {
                $products[] = $item;
            }
        }
        //loop over products to see if their qty is different from the warranty qty and adjust both to max
        $hasChanges = false;
        foreach ($products as $item) {
            /** @var \Magento\Quote\Model\Quote\Item $item */
            $sku = $item->getSku();
            foreach ($warranties as $warrantyItem) {
                /** @var \Magento\Quote\Model\Quote\Item $warrantyItem */
                if ($warrantyItem->getOptionByCode('associated_product')->getValue() == $sku
                    && ($item->getProductType() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
                        || is_null($item->getOptionByCode('parent_product_id'))) || $this->isGrouped($warrantyItem, $item))
                {
                    if ($warrantyItem->getQty() <> $item->getQty()) {
                        if ($item->getQty() > 0) {
                            //update warranty qty
                            $warrantyItem->setQty($item->getQty());
                            $warrantyItem->calcRowTotal();
                            try {
                                $warrantyItem->save();
                                $hasChanges = true;
                            } catch(\Exception $e) {}
                        }
                    }
                }
            }
        }
        //only collect totals and re-save the quote if something actually changed
        if ($hasChanges) {
            $quote->setTriggerRecollect(1);
            try {
                $quote->collectTotals()->save();
            } catch (\Exception $e) {}
        }
    }

    private function isGrouped($warrantyItem, $item) : bool
    {
        if ($warrantyItem->getOptionByCode('associated_product')->getValue() != $item->getSku()
            && $item->getProductType() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE ) {
            $groupedProductsIds = $this->_grouped->getParentIdsByChild($item->getProduct()->getId());
            $groupedProductId  = reset($groupedProductsIds);
            $groupedProduct = $this->_productRepository->getById($groupedProductId);
            if ($warrantyItem->getOptionByCode('associated_product')->getValue() == $groupedProduct->getSku()) {
                return true;
            }
        }
        return false;
    }
}
