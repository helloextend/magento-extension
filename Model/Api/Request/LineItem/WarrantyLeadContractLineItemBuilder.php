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

class WarrantyLeadContractLineItemBuilder extends WarrantyContractLineItemBuilder
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

        $lineItem = AbstractLineItemBuilder::preparePayload($orderItem);

        $leadToken = implode(',', $this->helper->unserialize($orderItem->getLeadToken()));

        $lineItem = array_merge([
            'leadToken' => $leadToken,
            'status' => $this->getStatus($orderItem),
            'plan' => $this->getPlan($orderItem),
            'discountAmount' => $this->helper->formatPrice(
                $orderItem->getDiscountAmount() / $orderItem->getQtyOrdered()
            ),
            'taxCost' => $this->helper->formatPrice(
                $orderItem->getTaxAmount() / $orderItem->getQtyOrdered()
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
        $result = true;

        if (!$this->dataHelper->isLeadEnabled($item->getStoreId())) {
            $result = false;
        }
        if ($item->getProductType() !== Type::TYPE_CODE) {
            $result = false;
        }

        if (!$item->getLeadToken()) {
            $result = false;
        }

        return $result;
    }
}
