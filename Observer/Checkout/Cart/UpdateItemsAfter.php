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

use Extend\Warranty\Model\Product\Type;

/**
 * Class UpdateItemsAfter
 *
 * Checkout Cart UpdateItemsAfter Observer
 */
class UpdateItemsAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Warranty Tracking Helper
     *
     * @var \Extend\Warranty\Helper\Tracking
     */
    private $_trackingHelper;

    /**
     * Warranty Api Data Helper
     *
     * @var \Extend\Warranty\Helper\Api\Data
     */
    private $dataHelper;

    /**
     * UpdateItemsAfter constructor
     *
     * @param \Extend\Warranty\Helper\Tracking $trackingHelper
     * @param \Extend\Warranty\Helper\Api\Data $dataHelper
     */
    public function __construct(
        \Extend\Warranty\Helper\Tracking $trackingHelper,
        \Extend\Warranty\Helper\Api\Data $dataHelper
    ) {
        $this->_trackingHelper = $trackingHelper;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Observer execute
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @noinspection PhpDeprecationInspection
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_trackingHelper->isTrackingEnabled()) {
            return;
        }
        /** @var \Magento\Checkout\Model\Cart $cart */
        $cart = $observer->getData('cart');
        if (!$cart instanceof \Magento\Checkout\Model\Cart) {
            return;
        }
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $cart->getQuote();
        if (!$quote instanceof \Magento\Quote\Model\Quote) {
            return;
        }
        foreach ($quote->getAllItems() as $quoteItem) {
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            $qty = (int)$quoteItem->getQty();
            $origQty = (int)$quoteItem->getOrigData('qty');
            if ($qty == $origQty) {
                continue;
            }
            if ($quoteItem->getProductType() === Type::TYPE_CODE) {
                if ($this->dataHelper->isBalancedCart()) {
                    continue;
                }
                //send tracking update for the warranty offer
                $planId = $quoteItem->getOptionByCode(Type::WARRANTY_ID);
                /** @var \Magento\Quote\Model\Quote\Item $item */
                $item = $this->_trackingHelper->getQuoteItemForWarrantyItem($quoteItem);
                if($planId && $planId->getValue()){
                    $trackingData = [
                        'eventName'        => 'trackOfferUpdated',
                        'productId'        => $item->getSku(),
                        'planId'           => $planId->getValue(),
                        'warrantyQuantity' => $qty,
                        'productQuantity'  => (int)$item->getQty(),
                    ];
                    $this->_trackingHelper->setTrackingData($trackingData);
                }
            } else {
                $warrantyItems = $this->_trackingHelper->getWarrantyItemsForQuoteItem($quoteItem);
                if (!count($warrantyItems)) {
                    //there is no associated warranty item, just send tracking for the product update
                    $trackingData = [
                        'eventName' => 'trackProductUpdated',
                        'productId' => $quoteItem->getSku(),
                        'productQuantity' => $qty,
                    ];
                    $this->_trackingHelper->setTrackingData($trackingData);
                } else {
                    foreach ($warrantyItems as $warrantyItem) {
                        $planId = $warrantyItem->getOptionByCode('warranty_id');
                        if ($planId && $planId->getValue()) {
                            $trackingData = [
                                'eventName'        => 'trackOfferUpdated',
                                'productId'        => $quoteItem->getSku(),
                                'planId'           => $planId->getValue(),
                                'warrantyQuantity' => (int)$warrantyItem->getQty(),
                                'productQuantity'  => $qty,
                            ];
                            $this->_trackingHelper->setTrackingData($trackingData);
                        }
                    }
                }
            }
        }
    }
}
