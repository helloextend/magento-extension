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

use Extend\Warranty\Model\Normalizer;
use Extend\Warranty\Model\Product\Type;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item;
use Psr\Log\LoggerInterface;

/**
 * Class UpdateItemComplete
 *
 * Checkout Cart UpdateItemComplete Observer
 */
class UpdateItemComplete implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Warranty Tracking Helper
     *
     * @var \Extend\Warranty\Helper\Tracking
     */
    private $_trackingHelper;

    /**
     * Normalizer Model
     *
     * @var Normalizer
     */
    private $normalizer;

    /**
     * Logger Model
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UpdateItemComplete constructor
     *
     * @param \Extend\Warranty\Helper\Tracking $trackingHelper
     * @param Normalizer $normalizer
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Extend\Warranty\Helper\Tracking $trackingHelper,
        Normalizer $normalizer,
        LoggerInterface $logger
    ) {
        $this->_trackingHelper = $trackingHelper;
        $this->normalizer = $normalizer;
        $this->logger = $logger;
    }

    /**
     * Observer execute
     *
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
            } elseif (!count($this->_trackingHelper->getWarrantyItemsForQuoteItem($quoteItem))) {
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
