<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Plugin\Quote\Model\Item;

use Extend\Warranty\Helper\Api\Magento\Data;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Class SetItemDataToOrderPlugin
 */
class SetItemDataToOrderPlugin
{
    /**
     * Apply item data to order item
     *
     * @param ToOrderItem $subject
     * @param OrderItemInterface $orderItem
     * @param AbstractItem $item
     * @param array $additional
     * @return OrderItemInterface
     */
    public function afterConvert(
        ToOrderItem $subject,
        OrderItemInterface $orderItem,
        AbstractItem $item,
        $additional = []
    ) {
        $leadToken = $item->getData(Data::LEAD_TOKEN);
        $orderItem->setData(Data::LEAD_TOKEN, $leadToken);

        $extensionAttributes = $orderItem->getExtensionAttributes();
        $extensionAttributes->setLeadToken($leadToken);
        $orderItem->setExtensionAttributes($extensionAttributes);

        return $orderItem;
    }
}
