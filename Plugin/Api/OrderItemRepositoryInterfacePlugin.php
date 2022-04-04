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

use Extend\Warranty\Helper\Api\Magento\Data;

/**
 * Class OrderItemRepositoryInterfacePlugin
 * @package Extend\Warranty\Plugin\Api
 */
class OrderItemRepositoryInterfacePlugin
{
    /**
     * Order Extension Attributes Factory
     *
     * @var \Magento\Sales\Api\Data\OrderItemExtensionFactory
     */
    private $magentoApiHelper;

    /**
     * OrderItemRepositoryInterfacePlugin constructor.
     * @param Data $magentoApiHelper
     */
    public function __construct(Data $magentoApiHelper)
    {
        $this->magentoApiHelper = $magentoApiHelper;
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
        $this->magentoApiHelper->setOrderItemExtensionAttributes($orderItem);
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
            $this->magentoApiHelper->setOrderItemExtensionAttributes($orderItem);
        }

        return $searchResult;
    }
}
