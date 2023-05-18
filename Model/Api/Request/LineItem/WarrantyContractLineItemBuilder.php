<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Request\LineItem;

use Extend\Warranty\Model\Config\Source\Event as CreateContractEvent;
use Extend\Warranty\Model\Product\Type;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;

class WarrantyContractLineItemBuilder extends AbstractLineItemBuilder
{
    /**
     * @param OrderItemInterface $orderItem
     * @return array
     */
    public function preparePayload($orderItem)
    {
        if (!$this->validate($orderItem)) {
            return [];
        }

        $warrantableOrderItem = $this->warrantyRelation->getAssociatedOrderItem($orderItem);

        $lineItem = parent::preparePayload($orderItem);

        $lineItem = array_merge([
            'status' => $this->getStatus($orderItem),
            'product' => $this->getProductPayload($warrantableOrderItem),
            'plan' => $this->getPlan($orderItem),
            'discountAmount' => $this->helper->formatPrice(
                $warrantableOrderItem->getDiscountAmount() / $warrantableOrderItem->getQtyOrdered()
            ),
            'taxCost' => $this->helper->formatPrice(
                $warrantableOrderItem->getTaxAmount() / $warrantableOrderItem->getQtyOrdered()
            ),
            'quantity' => $orderItem->getQtyOrdered()
        ], $lineItem);

        return $lineItem;
    }

    /**
     * @param OrderItemInterface $item
     * @return bool
     */
    protected function validate($item)
    {
        $result = true;
        if ($item->getProductType() !== Type::TYPE_CODE) {
            $result = false;
        }

        if ($item->getLeadToken()) {
            $result = false;
        }

        if (!$this->warrantyRelation->getAssociatedOrderItem($item)) {
            $result = false;
        }
        return $result;
    }

    /**
     * @param OrderItemInterface $orderItem
     * @return string
     */
    protected function getStatus($orderItem)
    {
        $orderItem->getStoreId();

        $contractCreateEvent = $this->dataHelper->getContractCreateEvent(
            ScopeInterface::SCOPE_STORES,
            $orderItem->getStoreId()
        );

        $status = 'unfulfilled';

        if ($contractCreateEvent == CreateContractEvent::ORDER_CREATE) {
            $status = 'fulfilled';
        }

        /**
         * if order item invoiced and shipped then it fulfilled
         */
        if (
            $orderItem->getQtyInvoiced() == $orderItem->getQtyOrdered()
            && $orderItem->getQtyInvoiced() == $orderItem->getQtyShipped()
        ) {
            $status = 'fulfilled';
        }

        return $status;
    }
}
