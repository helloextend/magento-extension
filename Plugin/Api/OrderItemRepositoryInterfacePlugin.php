<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */
namespace Extend\Warranty\Plugin\Api;

/**
 * Class OrderItemRepositoryInterfacePlugin
 * @package Extend\Warranty\Plugin\Api
 */
class OrderItemRepositoryInterfacePlugin
{
    /**
     * Extension attributes
     */
    const CONTRACT_ID        = 'contract_id';
    const PRODUCT_OPTIONS    = 'product_options';
    const WARRANTY_ID        = 'warranty_id';
    const ASSOCIATED_PRODUCT = 'associated_product';
    const REFUND             = 'refund';
    const WARRANTY_TERM      = 'warranty_term';

    /**
     * Order Extension Attributes Factory
     *
     * @var \Magento\Sales\Api\Data\OrderItemExtensionFactory
     */
    private $_extensionFactory;

    /**
     * Json Serializer
     *
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $_jsonSerializer;

    /**
     * List of product options
     */
    const PRODUCT_OPTION_LIST = [
        self::WARRANTY_ID,
        self::ASSOCIATED_PRODUCT,
        self::REFUND,
        self::WARRANTY_TERM,
    ];

    /**
     * OrderItemRepositoryInterfacePlugin constructor.
     * @param \Magento\Sales\Api\Data\OrderItemExtensionFactory $extensionFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     */
    public function __construct(
        \Magento\Sales\Api\Data\OrderItemExtensionFactory $extensionFactory,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
    )
    {
        $this->_extensionFactory = $extensionFactory;
        $this->_jsonSerializer = $jsonSerializer;
    }

    /**
     * Add "contract_id & product_options" extension attributes to order item data object to make it accessible in API data
     *
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\OrderItemInterface $orderItem
     * @return \Magento\Sales\Api\Data\OrderItemInterface
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterGet(
        \Magento\Sales\Api\OrderItemRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderItemInterface $orderItem
    ): \Magento\Sales\Api\Data\OrderItemInterface
    {
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        $contractId = (string)$orderItem->getData(self::CONTRACT_ID);
        $productOptions = (array)$orderItem->getProductOptions();
        $productOptionsJson = (string)$this->getProductOptionsJson($orderItem, $productOptions);

        foreach (self::PRODUCT_OPTION_LIST as $option) {
            $productOptions[$option] = $productOptions[$option] ?? null;
        }

        $extensionAttributes = $orderItem->getExtensionAttributes();
        /** @noinspection PhpUndefinedMethodInspection */
        $extensionAttributes = $extensionAttributes ?: $this->_extensionFactory->create();

        /** @noinspection PhpUndefinedMethodInspection */
        $extensionAttributes->setContractId($contractId);
        /** @noinspection PhpUndefinedMethodInspection */
        $extensionAttributes->setProductOptions($productOptionsJson);
        /** @noinspection PhpUndefinedMethodInspection */
        $extensionAttributes->setWarrantyId($productOptions[self::WARRANTY_ID] ?? '');
        /** @noinspection PhpUndefinedMethodInspection */
        $extensionAttributes->setAssociatedProduct($productOptions[self::ASSOCIATED_PRODUCT] ?? '');
        /** @noinspection PhpUndefinedMethodInspection */
        $extensionAttributes->setRefund((bool)$productOptions[self::REFUND]);
        /** @noinspection PhpUndefinedMethodInspection */
        $extensionAttributes->setTerm($productOptions[self::WARRANTY_TERM] ?? '');

        $orderItem->setExtensionAttributes($extensionAttributes);

        return $orderItem;
    }

    /**
     * Add "contract_id & product_options" extension attributes to order item data object to make it accessible in API data
     *
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\OrderItemSearchResultInterface $searchResult
     * @return \Magento\Sales\Api\Data\OrderItemSearchResultInterface
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterGetList(
        \Magento\Sales\Api\OrderItemRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderItemSearchResultInterface $searchResult
    ): \Magento\Sales\Api\Data\OrderItemSearchResultInterface
    {
        $ordersItems = $searchResult->getItems();

        foreach ($ordersItems as $orderItem) {
            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            $contractId = (string)$orderItem->getData(self::CONTRACT_ID);
            $productOptions = (array)$orderItem->getProductOptions();
            $productOptionsJson = (string)$this->getProductOptionsJson($orderItem, $productOptions);

            foreach (self::PRODUCT_OPTION_LIST as $option) {
                $productOptions[$option] = $productOptions[$option] ?? null;
            }

            $extensionAttributes = $orderItem->getExtensionAttributes();
            /** @noinspection PhpUndefinedMethodInspection */
            $extensionAttributes = $extensionAttributes ?: $this->_extensionFactory->create();

            /** @noinspection PhpUndefinedMethodInspection */
            $extensionAttributes->setContractId($contractId);
            /** @noinspection PhpUndefinedMethodInspection */
            $extensionAttributes->setProductOptions($productOptionsJson);
            /** @noinspection PhpUndefinedMethodInspection */
            $extensionAttributes->setWarrantyId($productOptions[self::WARRANTY_ID] ?? '');
            /** @noinspection PhpUndefinedMethodInspection */
            $extensionAttributes->setAssociatedProduct($productOptions[self::ASSOCIATED_PRODUCT] ?? '');
            /** @noinspection PhpUndefinedMethodInspection */
            $extensionAttributes->setRefund((bool)$productOptions[self::REFUND]);
            /** @noinspection PhpUndefinedMethodInspection */
            $extensionAttributes->setTerm($productOptions[self::WARRANTY_TERM] ?? '');

            $orderItem->setExtensionAttributes($extensionAttributes);
        }

        return $searchResult;
    }

    /**
     * Get product options JSON
     *
     * @param \Magento\Sales\Api\Data\OrderItemInterface $orderItem
     * @param array $productOptions
     * @return string
     * @noinspection PhpUnusedLocalVariableInspection
     */
    private function getProductOptionsJson(
        \Magento\Sales\Api\Data\OrderItemInterface $orderItem,
        array $productOptions
    ): string
    {
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        try {
            $productOptionsJson = $orderItem->getData(self::PRODUCT_OPTIONS);
            if (!is_string($productOptionsJson)) {
                $productOptionsJson = $this->_jsonSerializer->serialize($productOptions);
            }
        } catch (\Exception $e) {
            $productOptionsJson = '';
        }

        return $productOptionsJson;
    }
}
