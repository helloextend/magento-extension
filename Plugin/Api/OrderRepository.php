<?php

namespace Extend\Warranty\Plugin\Api;

use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Api\Data\OrderItemSearchResultInterface;

class OrderRepository
{

    const CONTRACT_ID = 'contract_id';
    const PRODUCT_OPTIONS = 'product_options';

    /**
     * Order Extension Attributes Factory
     *
     * @var OrderItemExtensionFactory
     */
    protected $extensionFactory;

    /**
     * OrderRepositoryPlugin constructor
     *
     * @param OrderExtensionFactory $extensionFactory
     */
    public function __construct(OrderItemExtensionFactory $extensionFactory)
    {
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * Add "contract_id & product_options" extension attributes to order data object to make it accessible in API data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     *
     * @return OrderInterface
     */
    public function afterGet(OrderItemRepositoryInterface $subject, OrderItemInterface $orderItem)
    {
        //contract_id
        $contractId = $orderItem->getData(self::CONTRACT_ID);
        //product_options
        $productOptions = $orderItem->getProductOptions();
        $extensionAttributes = $orderItem->getExtensionAttributes();
        $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();
        $extensionAttributes->setProductOptions(json_encode($productOptions));

        if ($contractId) {
            $extensionAttributes->setContractId($contractId);
            $extensionAttributes->setWarrantyId($productOptions['warranty_id']);
            $extensionAttributes->setAssociatedProduct($productOptions['associated_product']);
            $extensionAttributes->setTerm($productOptions['warranty_term']);
            $extensionAttributes->setRefund($productOptions['refund']);
        }
        $orderItem->setExtensionAttributes($extensionAttributes);

        return $orderItem;
    }

    /**
     * Add "contract_id & product_options" extension attributes to order data object to make it accessible in API data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $searchResult
     *
     * @return OrderSearchResultInterface
     */
    public function afterGetList(OrderItemRepositoryInterface $subject, OrderItemSearchResultInterface $searchResult)
    {
        $ordersItems = $searchResult->getItems();

        foreach ($ordersItems as &$orderItem) {
            //contract_id
            $contractId = $orderItem->getData(self::CONTRACT_ID);
            //product_options
            $productOptions = $orderItem->getProductOptions();
            $extensionAttributes = $orderItem->getExtensionAttributes();
            $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();
            $extensionAttributes->setProductOptions(json_encode($productOptions));

            if ($contractId) {
                $extensionAttributes->setContractId($contractId);
                $extensionAttributes->setWarrantyId($productOptions['warranty_id']);
                $extensionAttributes->setAssociatedProduct($productOptions['associated_product']);
                $extensionAttributes->setTerm($productOptions['warranty_term']);
                $extensionAttributes->setRefund($productOptions['refund']);
            }

            $orderItem->setExtensionAttributes($extensionAttributes);
        }

        return $searchResult;
    }
}