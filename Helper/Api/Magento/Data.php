<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

declare(strict_types=1);

namespace Extend\Warranty\Helper\Api\Magento;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Item;

/**
 * Helper class for adding Extend warranty extension attributes to order item
 * Class Data
 */
class Data extends AbstractHelper
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
    const LEAD_TOKEN         = 'lead_token';
    const PLAN_TYPE          = 'plan_type';

    /**
     * Order Extension Attributes Factory
     *
     * @var OrderItemExtensionFactory
     */
    private $_extensionFactory;

    /**
     * Json Serializer
     *
     * @var Json
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
        self::PLAN_TYPE,
    ];

    /**
     * OrderItemRepositoryInterfacePlugin constructor.
     * @param Context $context
     * @param OrderItemExtensionFactory $extensionFactory
     * @param Json $jsonSerializer
     */
    public function __construct(
        Context $context,
        OrderItemExtensionFactory $extensionFactory,
        Json $jsonSerializer
    ) {
        $this->_extensionFactory = $extensionFactory;
        $this->_jsonSerializer = $jsonSerializer;
        parent::__construct($context);
    }

    /**
     * Set "contract_id & product_options" extension attributes to order item
     *
     * @param OrderItemInterface $orderItem
     * @return void
     */
    public function setOrderItemExtensionAttributes(
        OrderItemInterface $orderItem
    ): void {
        $contractId = (string)$orderItem->getData(self::CONTRACT_ID);

        $buyRequest = $orderItem->getBuyRequest();

        if ($buyRequest && isset($buyRequest['leadToken'])) {
            $leadToken = $buyRequest['leadToken'];
        } else {
            $leadToken = '';
        }
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
        $extensionAttributes->setLeadToken($leadToken);
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
        /** @noinspection PhpUndefinedMethodInspection */
        $extensionAttributes->setPlanType($productOptions[self::PLAN_TYPE] ?? '');

        $orderItem->setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get product options JSON
     *
     * @param OrderItemInterface $orderItem
     * @param array $productOptions
     * @return string
     * @noinspection PhpUnusedLocalVariableInspection
     */
    private function getProductOptionsJson(
        OrderItemInterface $orderItem,
        array $productOptions
    ): string {
        /** @var Item $orderItem */
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
