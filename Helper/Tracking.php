<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */
namespace Extend\Warranty\Helper;

/**
 * Class Tracking
 * @package Extend\Warranty\Helper
 */
class Tracking extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**#@+
     * config constants
     */
    const XML_PATH_EXTEND_ENABLED   = 'warranty/enableExtend/enable';
    const XML_PATH_TRACKING_ENABLED = 'warranty/tracking/enabled';

    /**
     * @var Grouped
     */
    private $_grouped;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * Tracking constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Extend\Warranty\Helper\Grouped $groupedHelper
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Extend\Warranty\Helper\Grouped $groupedHelper,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->_grouped = $groupedHelper;
        $this->_customerSession = $customerSession;

        parent::__construct(
            $context
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isExtendEnabled($storeId = null) : bool
    {
        $isExtendEnabled = (bool)$this->scopeConfig->getValue(
            self::XML_PATH_EXTEND_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $isExtendEnabled;
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isTrackingEnabled($storeId = null) : bool
    {
        $isExtendEnabled = $this->isExtendEnabled($storeId);
        if (!$isExtendEnabled) {
            return false;
        }
        $isTrackingEnabled = (bool)$this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $isTrackingEnabled;
    }

    /**
     * @param array $trackingData
     */
    public function setTrackingData(array $trackingData)
    {
        $extendTrackingData = (array)$this->_customerSession->getData('extend_tracking_data');
        $extendTrackingData[] = $trackingData;
        /** @noinspection PhpUndefinedMethodInspection */
        $this->_customerSession->setData('extend_tracking_data', $extendTrackingData);
    }

    /**
     * @param bool $clear
     * @return array
     */
    public function getTrackingData($clear = true)
    {
        $extendTrackingData = (array)$this->_customerSession->getData('extend_tracking_data', $clear);

        return $extendTrackingData;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return false|\Magento\Quote\Model\Quote\Item
     */
    public function getQuoteItemForWarrantyItem(\Magento\Quote\Model\Quote\Item $quoteItem)
    {
        //find corresponding product and get qty
        $productSku = (string)$quoteItem->getOptionByCode('associated_product')->getValue();
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $quoteItem->getQuote();
        foreach ($quote->getAllItems() as $item) {
            /** @var \Magento\Quote\Model\Quote\Item $item */
            if ($item->getSku() == $productSku
                && ($item->getProductType() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
                    || is_null($item->getOptionByCode('parent_product_id')))
            )
            {
                return $item;
            }
        }

        return false;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return false|\Magento\Quote\Model\Quote\Item
     */
    public function getWarrantyItemForQuoteItem(\Magento\Quote\Model\Quote\Item $quoteItem)
    {
        $sku = $quoteItem->getSku();
        if ($quoteItem->getProductType() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
            $sku = $this->_grouped->getGroupedWarrantySku($quoteItem);
        }
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $quoteItem->getQuote();
        foreach ($quote->getAllItems() as $item) {
            /** @var \Magento\Quote\Model\Quote\Item $item */
            if ($item->getProductType() !== \Extend\Warranty\Model\Product\Type::TYPE_CODE) {
                continue;
            }
            $warrantySku = (string)$item->getOptionByCode('associated_product')->getValue();
            if ($warrantySku == $sku) {
                return $item;
            }
        }

        return false;
    }
}
