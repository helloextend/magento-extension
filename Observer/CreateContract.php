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
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Framework\Exception\LocalizedException;
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
     * DataHelper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * LoggerInterface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CreateContract constructor
     *
     * @param WarrantyContract $warrantyContract
     * @param DataHelper $dataHelper
     * @param LoggerInterface $logger
     */
    public function __construct (
        WarrantyContract $warrantyContract,
        DataHelper $dataHelper,
        LoggerInterface $logger
    ) {
        $this->warrantyContract = $warrantyContract;
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
        if ($this->dataHelper->isExtendEnabled() && $this->dataHelper->isWarrantyContractEnabled()) {
            $event = $observer->getEvent();
            $invoice = $event->getInvoice();
            $order = $invoice->getOrder();

            foreach ($invoice->getAllItems() as $invoiceItem) {
                $orderItem = $invoiceItem->getOrderItem();

                if ($orderItem->getProductType() === WarrantyType::TYPE_CODE) {
                    $qtyInvoiced = intval($invoiceItem->getQty());
                    try {
                        $this->warrantyContract->create($order, $orderItem, $qtyInvoiced);
                    } catch (LocalizedException $exception) {
                        $this->logger->error('Error during warranty contract creation. ' . $exception->getMessage());
                    }
                }
            }
        }
    }
}
