<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Observer\ContractCreate;

use Magento\Framework\Event\Observer;
use Extend\Warranty\Model\Product\Type as WarrantyType;
use Extend\Warranty\Model\CreateContract as WarrantyContractCreate;
use Magento\Framework\Event\ObserverInterface;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Extend\Warranty\Model\Config\Source\Event as CreateContractEvent;
use Psr\Log\LoggerInterface;

/**
 * Class InvoiceObserver
 *
 * Class for creating warranty contract after invoice
 */
class InvoiceObserver implements ObserverInterface
{
    /**
     * Warranty Contract Create
     *
     * @var WarrantyContractCreate
     */
    private $warrantyContractCreate;

    /**
     * Warranty Api DataHelper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param WarrantyContractCreate $warrantyContractCreate
     * @param DataHelper $dataHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        WarrantyContractCreate $warrantyContractCreate,
        DataHelper $dataHelper,
        LoggerInterface $logger
    ) {
        $this->warrantyContractCreate = $warrantyContractCreate;
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
            && ($this->dataHelper->getContractCreateEvent(ScopeInterface::SCOPE_STORES, $storeId) == CreateContractEvent::INVOICE_CREATE)
        ) {
            foreach ($invoice->getAllItems() as $invoiceItem) {
                $orderItem = $invoiceItem->getOrderItem();

                if ($orderItem->getProductType() !== WarrantyType::TYPE_CODE) {
                    continue;
                }

                $qtyInvoiced = (int)$invoiceItem->getQty();

                if (!$this->dataHelper->isContractCreateModeScheduled(ScopeInterface::SCOPE_STORES, $storeId)) {
                    try {
                        $this->warrantyContractCreate->createContract($order, $orderItem, $qtyInvoiced, $storeId);
                    } catch (LocalizedException $exception) {
                        $this->logger->error(
                            'Error during invoice event warranty contract creation. ' . $exception->getMessage()
                        );
                    }
                } else {
                    try {
                        $this->warrantyContractCreate->addContactToQueue($orderItem, $qtyInvoiced);
                    } catch (LocalizedException $exception) {
                        $this->logger->error($exception->getMessage());
                    }
                }

            }
        }
    }
}
