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

namespace Extend\Warranty\Observer;

use Magento\Framework\Event\Observer;
use Extend\Warranty\Model\Product\Type as WarrantyType;
use Magento\Framework\Event\ObserverInterface;
use Extend\Warranty\Model\WarrantyContract;
use Extend\Warranty\Model\Orders as ExtendOrder;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CreateContract
 */
class CreateContract implements ObserverInterface
{
    /**
     * Warranty Contract
     *
     * @var WarrantyContract
     */
    private $warrantyContract;

    /**
     * @var ExtendOrder
     */
    private $extendOrder;

    /**
     * DataHelper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CreateContract constructor
     *
     * @param WarrantyContract $warrantyContract
     * @param ExtendOrder $extendOrder
     * @param DataHelper $dataHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        WarrantyContract $warrantyContract,
        ExtendOrder $extendOrder,
        DataHelper $dataHelper,
        LoggerInterface $logger
    ) {
        $this->warrantyContract = $warrantyContract;
        $this->extendOrder = $extendOrder;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
    }

    /**
     * Create warranty contract for order item if item is invoiced
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $invoice = $event->getInvoice();
        $order = $invoice->getOrder();

        $storeId = $order->getStoreId();

        if (
            $this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId)
            && $this->dataHelper->isWarrantyContractEnabled($storeId)
        ) {
            foreach ($invoice->getAllItems() as $invoiceItem) {
                $orderItem = $invoiceItem->getOrderItem();

                if ($orderItem->getProductType() === WarrantyType::TYPE_CODE) {
                    $qtyInvoiced = intval($invoiceItem->getQty());
                    if (!$this->dataHelper->isOrdersApiEnabled(ScopeInterface::SCOPE_STORES, $storeId)) {
                        try {
                            $this->warrantyContract->create($order, $orderItem, $qtyInvoiced);
                        } catch (LocalizedException $exception) {
                            $this->logger->error('Error during warranty contract creation. ' . $exception->getMessage());
                        }
                    } else {
                        try {
                            if (implode(", ", json_decode($orderItem->getLeadToken(), true)) != null) {
                                $this->extendOrder->createOrder($order, $orderItem, $qtyInvoiced, \Extend\Warranty\Model\Orders::LEAD_CONTRACT);
                            } else {
                                $this->extendOrder->createOrder($order, $orderItem, $qtyInvoiced, \Extend\Warranty\Model\Orders::CONTRACT);
                            }
                        } catch (LocalizedException $exception) {
                            $this->logger->error('Error during warranty order api contract creation. ' . $exception->getMessage());
                        }
                    }
                }
            }
        }
    }
}
