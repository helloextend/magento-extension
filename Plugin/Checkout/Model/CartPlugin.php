<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */
namespace Extend\Warranty\Plugin\Checkout\Model;

use Extend\Warranty\Model\Product\Type as WarrantyProductType;

class CartPlugin
{
    /**
     * @param \Magento\Checkout\Model\Cart $subject
     * @param \Closure $proceed
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param true|null $qtyFlag
     * @return mixed
     */
    public function aroundAddOrderItem(
        \Magento\Checkout\Model\Cart $subject,
        \Closure $proceed,
        $orderItem,
        $qtyFlag = null
    ) {
        if ($orderItem->getProductType() == WarrantyProductType::TYPE_CODE
            && $orderItem->getLeadToken()
        ) {
            return $subject;
        }
        return $proceed($orderItem, $qtyFlag);
    }
}