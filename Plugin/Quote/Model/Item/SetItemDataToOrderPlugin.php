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

use Magento\Sales\Api\Data\OrderItemExtensionInterfaceFactory;
use Extend\Warranty\Helper\Api\Magento\Data;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Class SetItemDataToOrderPlugin
 *
 * SetItemDataToOrderPlugin plugin
 */
class SetItemDataToOrderPlugin
{
    /**
     * @var OrderItemExtensionInterfaceFactory
     */
    private $orderItemExtensionFactory;

    /**
     * SetItemDataToOrderPlugin constructor
     *
     * @param OrderItemExtensionInterfaceFactory $orderItemExtensionFactory
     */
    public function __construct(
        OrderItemExtensionInterfaceFactory $orderItemExtensionFactory
    ) {
        $this->orderItemExtensionFactory = $orderItemExtensionFactory;
    }

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

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->orderItemExtensionFactory->create();
        }

        $extensionAttributes->setLeadToken($leadToken);
        $orderItem->setExtensionAttributes($extensionAttributes);

        return $orderItem;
    }
}
