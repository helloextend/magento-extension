<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */
namespace Extend\Warranty\Observer;

use Extend\Warranty\Model\Normalizer;
use Extend\Warranty\Model\Product\Type as WarrantyProductType;

/**
 * Class QuoteRemoveItem
 *
 * QuoteRemoveItem Observer
 */
class QuoteRemoveItem implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Extend\Warranty\Helper\Tracking
     */
    protected $_trackingHelper;

    protected $_normalizer;

    /**
     * QuoteRemoveItem constructor.
     * @param \Extend\Warranty\Helper\Tracking $trackingHelper
     */
    public function __construct(
        \Extend\Warranty\Helper\Tracking $trackingHelper,
        Normalizer $normalizer
    ) {
        $this->_trackingHelper = $trackingHelper;
        $this->_normalizer = $normalizer;
    }

    /**
     * Observer execute
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getData('quote_item');
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $quoteItem->getQuote();

        //if the item being removed is a warranty offer, send tracking for the offer removed, if tracking enabled
        if ($quoteItem->getProductType() === WarrantyProductType::TYPE_CODE) {
            if ($this->_trackingHelper->isTrackingEnabled()) {
                $warrantySku = $quoteItem->getOptionByCode(WarrantyProductType::ASSOCIATED_PRODUCT)
                    ? (string)$quoteItem->getOptionByCode(WarrantyProductType::ASSOCIATED_PRODUCT)->getValue()
                    : '';

                $planId = $quoteItem->getOptionByCode(WarrantyProductType::WARRANTY_ID)
                    ? (string)$quoteItem->getOptionByCode(WarrantyProductType::WARRANTY_ID)->getValue()
                    : '';

                if($warrantySku && $planId) {
                    $trackingData = [
                        'eventName' => 'trackOfferRemovedFromCart',
                        'productId' => $warrantySku,
                        'planId' => $planId,
                    ];

                    $this->_trackingHelper->setTrackingData($trackingData);

                    $trackProduct = true;
                    $items = $quote->getAllItems();
                    foreach ($items as $item) {
                        if ($item->getSku() === $warrantySku) {
                            $trackProduct = false;
                            break;
                        }
                    }

                    if ($trackProduct) {
                        $trackingData = [
                            'eventName' => 'trackProductRemovedFromCart',
                            'productId' => $warrantySku,
                        ];

                        $this->_trackingHelper->setTrackingData($trackingData);
                    }
                }
            }
        }else{
            //this is a regular product, check if there is an associated warranty item
            /** @var \Magento\Quote\Model\Quote\Item $warrantyItem */

            $warrantyItems = $this->_trackingHelper->getWarrantyItemsForQuoteItem($quoteItem);
            if (!count($warrantyItems) && $this->_trackingHelper->isTrackingEnabled()) {
                //there is no associated warranty item. Just track the product removal
                $sku = $quoteItem->getSku();
                $trackingData = [
                    'eventName' => 'trackProductRemovedFromCart',
                    'productId' => $sku,
                ];
                $this->_trackingHelper->setTrackingData($trackingData);
                return;
            }
        }

        /** Normalizer will kick rellated warranties for warrantable product */
        $this->_normalizer->normalize($quote);
    }
}
