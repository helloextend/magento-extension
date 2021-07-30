<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

declare(strict_types=1);

namespace Extend\Warranty\Plugin\Api;

use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderItemSearchResultInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class OrderItemRepositoryInterfacePlugin
 */
class OrderItemRepositoryInterfacePlugin
{
    /**
     * Extension attributes
     */
    const CONTRACT_ID = 'contract_id';
    const PRODUCT_OPTIONS = 'product_options';
    const WARRANTY_ID = 'warranty_id';
    const ASSOCIATED_PRODUCT = 'associated_product';
    const REFUND = 'refund';
    const WARRANTY_TERM = 'warranty_term';

    /**
     * Order Extension Attributes Factory
     *
     * @var OrderItemExtensionFactory
     */
    private $extensionFactory;

    /**
     * Json Serializer
     *
     * @var JsonSerializer
     */
    private $jsonSerializer;

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
     * OrderItemRepositoryInterfacePlugin constructor
     *
     * @param OrderItemExtensionFactory $extensionFactory
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        OrderItemExtensionFactory $extensionFactory,
        JsonSerializer $jsonSerializer
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Add "contract_id & product_options" extension attributes to order item data object to make it accessible in API data
     *
     * @param OrderItemRepositoryInterface $subject
     * @param OrderItemInterface $orderItem
     * @return OrderItemInterface
     */
    public function afterGet(
        OrderItemRepositoryInterface $subject,
        OrderItemInterface $orderItem
    ): OrderItemInterface {
        $contractId = $orderItem->getData(self::CONTRACT_ID);
        $productOptions = $orderItem->getProductOptions();
        $productOptionsJson = $this->getProductOptionsJson($orderItem, $productOptions);

        foreach (self::PRODUCT_OPTION_LIST as $option) {
            $productOptions[$option] = $productOptions[$option] ?? null;
        }

        $extensionAttributes = $orderItem->getExtensionAttributes();
        $extensionAttributes = $extensionAttributes ?: $this->extensionFactory->create();

        $extensionAttributes->setContractId($contractId);
        $extensionAttributes->setProductOptions($productOptionsJson);
        $extensionAttributes->setWarrantyId($productOptions[self::WARRANTY_ID]);
        $extensionAttributes->setAssociatedProduct($productOptions[self::ASSOCIATED_PRODUCT]);
        $extensionAttributes->setRefund($productOptions[self::REFUND]);
        $extensionAttributes->setTerm($productOptions[self::WARRANTY_TERM]);

        $orderItem->setExtensionAttributes($extensionAttributes);

        return $orderItem;
    }

    /**
     * Add "contract_id & product_options" extension attributes to order item data object to make it accessible in API data
     *
     * @param OrderItemRepositoryInterface $subject
     * @param OrderItemSearchResultInterface $searchResult
     * @return OrderItemSearchResultInterface
     */
    public function afterGetList(
        OrderItemRepositoryInterface $subject,
        OrderItemSearchResultInterface $searchResult
    ): OrderItemSearchResultInterface {
        $ordersItems = $searchResult->getItems();

        foreach ($ordersItems as &$orderItem) {
            $contractId = $orderItem->getData(self::CONTRACT_ID);
            $productOptions = $orderItem->getProductOptions();
            $productOptionsJson = $this->getProductOptionsJson($orderItem, $productOptions);

            foreach (self::PRODUCT_OPTION_LIST as $option) {
                $productOptions[$option] = $productOptions[$option] ?? null;
            }

            $extensionAttributes = $orderItem->getExtensionAttributes();
            $extensionAttributes = $extensionAttributes ?: $this->extensionFactory->create();

            $extensionAttributes->setContractId($contractId);
            $extensionAttributes->setProductOptions($productOptionsJson);
            $extensionAttributes->setWarrantyId($productOptions[self::WARRANTY_ID]);
            $extensionAttributes->setAssociatedProduct($productOptions[self::ASSOCIATED_PRODUCT]);
            $extensionAttributes->setRefund($productOptions[self::REFUND]);
            $extensionAttributes->setTerm($productOptions[self::WARRANTY_TERM]);

            $orderItem->setExtensionAttributes($extensionAttributes);
        }

        return $searchResult;
    }

    /**
     * Get product options JSON
     *
     * @param OrderItemInterface $orderItem
     * @param array $productOptions
     * @return string
     */
    private function getProductOptionsJson(OrderItemInterface $orderItem, array $productOptions): string
    {
        try {
            $productOptionsJson = $orderItem->getData(self::PRODUCT_OPTIONS);
            if (!is_string($productOptionsJson)) {
                $productOptionsJson = $this->jsonSerializer->serialize($productOptions);
            }
        } catch (LocalizedException $exception) {
            $productOptionsJson = '';
        }

        return $productOptionsJson;
    }
}
