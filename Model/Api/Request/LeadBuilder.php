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

namespace Extend\Warranty\Model\Api\Request;

use Extend\Warranty\Helper\Data as Helper;
use Magento\Framework\Locale\Currency;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Class LeadBuilder
 */
class LeadBuilder
{
    /**
     * Helper
     *
     * @var Helper
     */
    private $helper;

    /**
     * LeadBuilder constructor
     *
     * @param Helper $helper
     */
    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Prepare payload
     *
     * @param OrderInterface $order
     * @param OrderItemInterface $orderItem
     * @return array
     */
    public function preparePayload(OrderInterface $order, OrderItemInterface $orderItem): array
    {
        $customer['email'] = $order->getCustomerEmail();

        $price = [
            'currencyCode'  => $order->getOrderCurrencyCode() ?? Currency::DEFAULT_CURRENCY,
            'amount'        => $this->helper->formatPrice($orderItem->getPrice()),
        ];

        $product = [
            'purchasePrice'     => $price,
            'referenceId'       => $orderItem->getSku(),
            'transactionDate'   => time(),
            'transactionId'     => $order->getIncrementId(),
        ];

        $payload = [
            'customer'  => $customer,
            'quantity'  => $orderItem->getQtyOrdered(),
            'product'   => $product,
        ];

        return $payload;
    }
}
