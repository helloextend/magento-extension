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
 * Class Add
 * @package Extend\Warranty\Observer\Checkout\Cart
 */
class Add implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Extend\Warranty\Helper\Tracking
     */
    private $_trackingHelper;

    /**
     * Add constructor.
     * @param \Extend\Warranty\Helper\Tracking $trackingHelper
     */
    public function __construct(
        \Extend\Warranty\Helper\Tracking $trackingHelper
    )
    {
        $this->_trackingHelper = $trackingHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\App\RequestInterface $request */
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $observer->getData('request');
        $warrantyData = $request->getPost('warranty', []);

        if ($this->_trackingHelper->isTrackingEnabled() && empty($warrantyData)) {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $observer->getData('product');
            if (!$product instanceof \Magento\Catalog\Model\Product) {
                return;
            }

            $qty = (int)$request->getPost('qty', 1);
            $trackingData = [
                'eventName' => 'trackProductAddedToCart',
                'productId' => $product->getSku(),
                'productQuantity' => $qty,
            ];
            $this->_trackingHelper->setTrackingData($trackingData);
        }
    }
}
