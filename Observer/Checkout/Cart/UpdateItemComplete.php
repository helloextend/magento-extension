<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */
namespace Extend\Warranty\Observer\Checkout\Cart;

/**
 * Class UpdateItemComplete
 * @package Extend\Warranty\Observer\Checkout\Cart
 */
class UpdateItemComplete implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Extend\Warranty\Helper\Tracking
     */
    private $_trackingHelper;

    /**
     * UpdateItemComplete constructor.
     * @param \Extend\Warranty\Helper\Tracking $trackingHelper
     */
    public function __construct(
        \Extend\Warranty\Helper\Tracking $trackingHelper
    ) {
        $this->_trackingHelper = $trackingHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_trackingHelper->isTrackingEnabled()) {
            return;
        }
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getData('item');
        if (!$quoteItem instanceof \Magento\Quote\Model\Quote\Item) {
            return;
        }
        $qty = (int)$quoteItem->getQty();
        $origQty = (int)$quoteItem->getOrigData('qty');
        if ($qty <> $origQty) {
            if ($quoteItem->getProductType() === \Extend\Warranty\Model\Product\Type::TYPE_CODE) {
                //send tracking update for the warranty offer
                $planId = (string)$quoteItem->getOptionByCode('warranty_id')->getValue();
                /** @var \Magento\Quote\Model\Quote\Item $item */
                $item = $this->_trackingHelper->getQuoteItemForWarrantyItem($quoteItem);
                $trackingData = [
                    'eventName'        => 'trackOfferUpdated',
                    'productId'        => $quoteItem->getSku(),
                    'planId'           => $planId,
                    'warrantyQuantity' => $qty,
                    'productQuantity'  => (int)$item->getQty(),
                ];
                $this->_trackingHelper->setTrackingData($trackingData);
            } elseif (!$this->_trackingHelper->getWarrantyItemForQuoteItem($quoteItem)) {
                //there is no associated warranty item, just send tracking for the product update
                $trackingData = [
                    'eventName'       => 'trackProductUpdated',
                    'productId'       => $quoteItem->getSku(),
                    'productQuantity' => $qty,
                ];
                $this->_trackingHelper->setTrackingData($trackingData);
            }
        }
    }
}
