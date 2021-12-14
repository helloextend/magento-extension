<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */
namespace Extend\Warranty\Model;

/**
 * Class Normalizer
 * @package Extend\Warranty\Model
 */
class Normalizer
{
    /**
     * @var \Extend\Warranty\Helper\Tracking
     */
    private $_trackingHelper;

    /**
     * @var \Extend\Warranty\Helper\Grouped
     */
    private $_groupedHelper;

    /**
     * Normalizer constructor.
     * @param \Extend\Warranty\Helper\Tracking $trackingHelper
     * @param \Extend\Warranty\Helper\Grouped $groupedHelper
     */
    public function __construct(
        \Extend\Warranty\Helper\Tracking $trackingHelper,
        \Extend\Warranty\Helper\Grouped $groupedHelper
    )
    {
        $this->_trackingHelper = $trackingHelper;
        $this->_groupedHelper = $groupedHelper;
    }

    /**
     * @param \Magento\Checkout\Model\Cart $cart
     * @noinspection PhpDeprecationInspection
     */
    public function normalize(\Magento\Checkout\Model\Cart $cart)
    {
        //split cart items from products and warranties
        $warranties = [];
        $products = [];
        foreach ($cart->getItems() as $item) {
            /** @var \Magento\Quote\Model\Quote\Item $item */
            if ($item->getProductType() === \Extend\Warranty\Model\Product\Type::TYPE_CODE) {
                $warranties[$item->getItemId()] = $item;
            } else {
                $products[] = $item;
            }
        }
        //Loop products to see if their qty is different from the warranty qty and adjust both to max
        foreach ($products as $item) {
            /** @var \Magento\Quote\Model\Quote\Item $item */
            $sku = $item->getSku();
            foreach ($warranties as $warrantyItem) {
                /** @var \Magento\Quote\Model\Quote\Item $warrantyItem */
                if (($warrantyItem->getOptionByCode('associated_product')->getValue() == $sku
                    && ($item->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
                        || is_null($item->getOptionByCode('parent_product_id')))) || $this->_groupedHelper->isGroupedWarranty($warrantyItem, $item))
                {
                    if ($warrantyItem->getQty() <> $item->getQty()) {
                        if ($item->getQty() > 0) {
                            //update warranty qty
                            $warrantyItem->setQty($item->getQty());
                        } else {
                            //remove both product and warranty
                            $cart->removeItem($warrantyItem->getItemId());
                            $cart->removeItem($item->getItemId());
                        }
                    }
                }
            }
        }
    }
}
