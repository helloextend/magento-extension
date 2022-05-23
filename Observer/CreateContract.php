<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Observer;

use Magento\Framework\Event\Observer;
use Extend\Warranty\Model\Product\Type as WarrantyType;
use Magento\Framework\Event\ObserverInterface;
use Extend\Warranty\Model\WarrantyContract;
use Extend\Warranty\Model\Orders as ExtendOrder;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Config\Source\CreateContractApi;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Class CreateContractApi
 */
class CreateContract implements ObserverInterface
{
    /**
     * Warranty Contract Model
     *
     * @var WarrantyContract
     */
    private $warrantyContract;

    /**
     * ExtendOrder Model
     *
     * @var ExtendOrder
     */
    private $extendOrder;

    /**
     * Warranty Api DataHelper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Logger Model
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CreateContractApi constructor
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
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $invoice = $event->getInvoice();
        $order = $invoice->getOrder();

        $storeId = $order->getStoreId();

        if ($this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId)
            && $this->dataHelper->isWarrantyContractEnabled($storeId)
            && !$this->dataHelper->isContractCreateModeScheduled(ScopeInterface::SCOPE_STORES, $storeId)
        ) {
            foreach ($invoice->getAllItems() as $invoiceItem) {
                $orderItem = $invoiceItem->getOrderItem();

                if ($orderItem->getProductType() !== WarrantyType::TYPE_CODE) {
                    continue;
                }
                $qtyInvoiced = (int)$invoiceItem->getQty();

                if ($this->dataHelper->getContractCreateApi(ScopeInterface::SCOPE_STORES, $storeId) ==
                    CreateContractApi::CONTACTS_API
                ) {
                    try {
                        if ($orderItem->getLeadToken() != null &&
                            implode(", ", json_decode($orderItem->getLeadToken(), true)) != null
                        ) {
                            $this->warrantyContract->create(
                                $order,
                                $orderItem,
                                $qtyInvoiced,
                                \Extend\Warranty\Model\WarrantyContract::LEAD_CONTRACT
                            );
                        } else {
                            $this->warrantyContract->create(
                                $order,
                                $orderItem,
                                $qtyInvoiced,
                                \Extend\Warranty\Model\WarrantyContract::CONTRACT
                            );
                        }
                    } catch (LocalizedException $exception) {
                        $this->logger->error(
                            'Error during warranty contract creation. ' . $exception->getMessage()
                        );
                    }
                } elseif ($this->dataHelper->getContractCreateApi(ScopeInterface::SCOPE_STORES, $storeId) ==
                    CreateContractApi::ORDERS_API
                ) {
                    try {
                        if ($orderItem->getLeadToken() != null &&
                            implode(", ", json_decode($orderItem->getLeadToken(), true)) != null
                        ) {
                            $this->extendOrder->createOrder(
                                $order,
                                $orderItem,
                                $qtyInvoiced,
                                \Extend\Warranty\Model\Orders::LEAD_CONTRACT
                            );
                        } else {
                            $this->extendOrder->createOrder(
                                $order,
                                $orderItem,
                                $qtyInvoiced,
                                \Extend\Warranty\Model\Orders::CONTRACT
                            );
                        }
                    } catch (LocalizedException $exception) {
                        $this->logger->error(
                            'Error during warranty order api contract creation. ' . $exception->getMessage()
                        );
                    }
                }
            }
        }
    }
}
