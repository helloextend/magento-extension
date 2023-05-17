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
     * @return void
     */
    protected function validate($item)
    {
        if ($item->getProductType() !== Type::TYPE_CODE) {
            return false;
        }

        if ($item->getLeadToken()) {
            return false;
        }

        return true;
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

        return $status;
    }
}
