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
 * Class OrderRepositoryInterfacePlugin
 * @package Extend\Warranty\Plugin\Api
 */
class OrderRepositoryInterfacePlugin
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
     * @param \Magento\Sales\Api\OrderRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterGet(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderInterface $order
    ): \Magento\Sales\Api\Data\OrderInterface
    {
        $ordersItems = $order->getItems();

        foreach ($ordersItems as $orderItem) {
            $this->magentoApiHelper->setOrderItemExtensionAttributes($orderItem);
        }

        return $order;
    }

    /**
     * Add "contract_id & product_options" extension attributes to order item data object to make it accessible in API data
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\OrderSearchResultInterface $searchResult
     * @return \Magento\Sales\Api\Data\OrderSearchResultInterface
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterGetList(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderSearchResultInterface $searchResult
    ): \Magento\Sales\Api\Data\OrderSearchResultInterface
    {
        $orders = $searchResult->getItems();

        foreach ($orders as $order) {
            $ordersItems = $order->getItems();
            foreach ($ordersItems as $orderItem) {
                $this->magentoApiHelper->setOrderItemExtensionAttributes($orderItem);
            }
        }

        return $searchResult;
    }
}
