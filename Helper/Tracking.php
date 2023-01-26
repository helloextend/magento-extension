<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */
namespace Extend\Warranty\Helper;

use Extend\Warranty\Model\Product\Type;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote;
use Extend\Warranty\Model\WarrantyRelation;

/**
 * Class Tracking
 *
 * Warranty Tracking Helper
 */
class Tracking extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**#@+
     * config constants
     */
    public const XML_PATH_EXTEND_ENABLED   = 'warranty/enableExtend/enable';
    public const XML_PATH_TRACKING_ENABLED = 'warranty/tracking/enabled';

    /**
     * Customer Session Model
     *
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * @var WarrantyRelation
     */
    private $warrantyRelation;

    /**
     * Tracking constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        WarrantyRelation $warrantyRelation
    ) {
        $this->_customerSession = $customerSession;

        $this->warrantyRelation = $warrantyRelation;
        parent::__construct(
            $context
        );
    }

    /**
     * Is extend enabled
     *
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
     * Is tracking enabled
     *
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
     * Set tracking data
     *
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
     * Get tracking data
     *
     * @param bool $clear
     * @return array
     */
    public function getTrackingData($clear = true)
    {
        $extendTrackingData = (array)$this->_customerSession->getData('extend_tracking_data', $clear);

        return $extendTrackingData;
    }

    /**
     * Get Quote Item For Warranty Item
     *
     * @param Item $quoteItem
     * @return false|Item
     */
    public function getQuoteItemForWarrantyItem(Item $quoteItem)
    {
        //find corresponding product and get qty
        $productSku = (string)$quoteItem->getOptionByCode('associated_product')->getValue();
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $quoteItem->getQuote();
        foreach ($quote->getAllItems() as $item) {
            $sku = $item->getSku();
            $product = $item->getProduct();

            if ($product->hasCustomOptions() && $product->getTypeId() === \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
                $sku = $product->getData('sku');
            }

            /** @var \Magento\Quote\Model\Quote\Item $item */
            if ($sku == $productSku
                && ($item->getProductType() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
                    || null === $item->getOptionByCode('parent_product_id'))
            ) {
                return $item;
            }
        }

        return false;
    }

    /**
     * Get Warranty Items For Quote Item
     *
     * @param Item $quoteItem
     * @return Item[]
     */
    public function getWarrantyItemsForQuoteItem(Item $quoteItem)
    {
        $possibleItems = [];

        /** @var Quote $quote */
        $quote = $quoteItem->getQuote();

        /** @var Item $item */
        foreach ($quote->getAllItems() as $item) {

            if ($item->getProductType() !== Type::TYPE_CODE) {
                continue;
            } else {
                if ($this->warrantyRelation->isWarrantyRelatedToQuoteItem($item, $quoteItem, true)) {
                    $possibleItems[] = $item;
                }
            }
        }

        return $possibleItems;
    }
}
