<?php
/**
 * @author     Guidance Magento Team <magento@guidance.com>
 * @copyright  Copyright (c) 2023 Guidance Solutions (http://www.guidance.com)
 */

namespace Extend\Warranty\Model\Api\Request\LineItem;

use Extend\Warranty\Helper\Data;
use Extend\Warranty\Model\Api\Request\LineItemBuilder;
use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Model\WarrantyRelation;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;

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

        $lineItem = [
            'status' => $this->getStatus(),
            'product' => $this->getProductPayload($warrantableOrderItem),
            'plan' => $this->getPlan($orderItem),
            'discountAmount' => $warrantableOrderItem->getDiscountAmount() / $warrantableOrderItem->getQtyOrdered(),
            'taxCost' => $warrantableOrderItem->getTaxAmount() / $warrantableOrderItem->getQtyOrdered(),
            'quantity' => $orderItem->getQtyOrdered()
        ];

        return $lineItem;
    }

    /**
     * @param $warrantableOrderItem
     * @return []
     */
    protected function getProductPayload($warrantableOrderItem)
    {
        $product = $warrantableOrderItem->getProduct();

        if (!$product) {
            $product = $this->getCatalogProduct(
                $this->warrantyRelation->getOfferOrderItemSku(
                    $warrantableOrderItem
                )
            );
        }

        if (!$product) {
            return [];
        }

        $productDataBuilder = $this->productDataBuilderFactory->create();

        $productPayload = $productDataBuilder->preparePayload($product);
        $productPayload['listPrice'] = $this->helper->formatPrice(
            $productDataBuilder->calculateSyncProductPrice($product)
        );
        $productPayload['purchasePrice'] = $this->helper->formatPrice(
            $warrantableOrderItem->getRowTotal() / $warrantableOrderItem->getQtyOrdered()
        );

        return $productPayload;
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
    }
}