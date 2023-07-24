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

use Extend\Warranty\Model\Api\Request\LineItemBuilder;
use Extend\Warranty\Model\Product\Type;
use Magento\Sales\Api\Data\OrderItemInterface;

class LeadLineItemBuilder extends AbstractLineItemBuilder
{
    public function preparePayload($item)
    {
        if (!$this->validate($item)) {
            return [];
        }

        $lineItem = parent::preparePayload($item);

        $product = $this->getProductPayload($item);

        $lineItem = array_merge($lineItem, [
            'status' => $this->getStatus(),
            'discountAmount' => $this->helper->formatPrice(
                $item->getDiscountAmount() / $item->getQtyOrdered()
            ),
            'taxCost' => $this->helper->formatPrice(
                $item->getTaxAmount() / $item->getQtyOrdered()
            ),
            'product' => $product,
            'quantity' => $this->getLeadsQty($item)
        ]);

        return $lineItem;
    }

    /**
     * @param OrderItemInterface $item
     * @return float
     */
    protected function getLeadsQty($item)
    {
        $warrantiesQty = 0;

        foreach ($this->warrantyRelation->getOrderItemWarranty($item) as $warrantyItem) {
            $warrantiesQty += $warrantyItem->getQtyOrdered();
        }

        if ($warrantiesQty <= $item->getQtyOrdered()) {
            return $item->getQtyOrdered() - $warrantiesQty;
        }

        return $item->getQtyOrdered();
    }

    /**
     * @param OrderItemInterface $item
     * @return bool
     */
    protected function validate($item)
    {
        $result = true;
        if ($item->getProductType() === Type::TYPE_CODE) {
            $result = false;
        }

        /**
         * We don't want to create new lead if it already created
         * It possible if it was created by old code
         * and create order initiated after new code deployed.
         *
         */
        if ($item->getLeadToken()) {
            $result = false;
        }

        /**
         * If amount of warranties is less then associated products
         * then we should create a leads for the rest.
         */
        if ($this->getLeadsQty($item) == 0) {
            $result = false;
        }
        return $result;
    }

    /**
     * we want create lead immediately, so set "fulfilled"
     * @return string
     */
    protected function getStatus()
    {
        return 'fulfilled';
    }
}