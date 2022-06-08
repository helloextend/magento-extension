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
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Extend\Warranty\Model\Config\Source\Event as CreateContractEvent;
use Psr\Log\LoggerInterface;

/**
 * Class OrderObserver
 *
 * Class for creating warranty contract after order
 */
class OrderObserver implements ObserverInterface
{
    /**
     * Warranty Contract Model
     *
     * @var WarrantyContract
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
     * Create warranty contract for order item if item is ordered
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();

        $storeId = $order->getStoreId();
        $contractCreateEvent = $this->dataHelper->getContractCreateEvent(ScopeInterface::SCOPE_STORES, $storeId);

        if ($this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId)
            && $this->dataHelper->isWarrantyContractEnabled($storeId)
            && ($contractCreateEvent == CreateContractEvent::ORDER_CREATE)
            && $this->orderAllowState($order)
        ) {
            foreach ($order->getItems() as $orderItem) {
                if ($orderItem->getProductType() !== WarrantyType::TYPE_CODE) {
                    continue;
                }

                if ($orderItem->getContractId() !== null || $orderItem->getLeadToken() !== null) {
                    continue;
                }

                $qtyOrdered = (int)$orderItem->getQtyOrdered();

                if (!$this->dataHelper->isContractCreateModeScheduled(ScopeInterface::SCOPE_STORES, $storeId)) {
                    try {
                        $this->warrantyContractCreate->createContract($order, $orderItem, $qtyOrdered, $storeId);
                    } catch (LocalizedException $exception) {
                        $this->warrantyContractCreate->addContractToQueue($orderItem, $qtyOrdered);
                        $this->logger->error(
                            'Error during shipment event warranty contract creation. ' . $exception->getMessage()
                        );
                    }
                } else {
                    try {
                        $this->warrantyContractCreate->addContractToQueue($orderItem, $qtyOrdered);
                    } catch (LocalizedException $exception) {
                        $this->logger->error($exception->getMessage());
                    }
                }
            }
        }
    }

    /**
     * @param OrderInterface $order
     * @return bool
     */
    private function orderAllowState(OrderInterface $order): bool
    {
        //Allowed order statuses
        $statuses = ['pending', 'processing', 'complete'];

        if (in_array($order->getStatus(), $statuses)) {
            return true;
        }
        return false;
    }
}
