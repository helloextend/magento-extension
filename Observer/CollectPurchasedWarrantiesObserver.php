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

use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Extend\Warranty\Model\Product\Type as WarrantyType;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\ContractCreateFactory;
use Extend\Warranty\Model\ResourceModel\ContractCreate as ContractCreateResource;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Exception;

/**
 * Class CollectPurchasedWarrantiesObserver
 */
class CollectPurchasedWarrantiesObserver implements ObserverInterface
{
    /**
     * `Invoice Item ID` field
     */
    const INVOICE_ITEM_ID = 'invoice_item_id';

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Contract Create Factory
     *
     * @var ContractCreateFactory
     */
    private $contractCreateFactory;

    /**
     * Contract Create Resource
     *
     * @var ContractCreateResource
     */
    private $contractCreateResource;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CollectPurchasedWarrantiesObserver constructor
     *
     * @param DataHelper $dataHelper
     * @param ContractCreateFactory $contractCreateFactory
     * @param ContractCreateResource $contractCreateResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        DataHelper $dataHelper,
        ContractCreateFactory $contractCreateFactory,
        ContractCreateResource $contractCreateResource,
        LoggerInterface $logger
    ) {
        $this->dataHelper = $dataHelper;
        $this->contractCreateFactory = $contractCreateFactory;
        $this->contractCreateResource = $contractCreateResource;
        $this->logger = $logger;
    }

    /**
     * Collect purchased warranties
     *
     * @param Observer $observer
     * @throws Exception
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $invoice = $event->getData(InvoiceItemInterface::INVOICE);
        $storeId = $invoice->getStoreId();

        if (
            $this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId)
            && $this->dataHelper->getContractCreateMode(ScopeInterface::SCOPE_STORES, $storeId)
        ) {
            foreach ($invoice->getAllItems() as $invoiceItem) {
                $orderItem = $invoiceItem->getOrderItem();
                $productType = $orderItem->getProductType();
                if ($productType === WarrantyType::TYPE_CODE) {
                    try {
                        $contractCreate = $this->contractCreateFactory->create();
                        $contractCreate->setData([
                            InvoiceItemInterface::ORDER_ITEM_ID => $orderItem->getId(),
                            self::INVOICE_ITEM_ID => $invoiceItem->getId(),
                            OrderItemInterface::QTY_INVOICED => $invoiceItem->getQty(),
                        ]);
                        $this->contractCreateResource->save($contractCreate);
                    } catch (LocalizedException $exception) {
                        $this->logger->error($exception->getMessage());
                    }
                }
            }
        }
    }
}
