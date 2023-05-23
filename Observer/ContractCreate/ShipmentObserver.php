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

use Extend\Warranty\Model\WarrantyRelation;
use Magento\Framework\Event\Observer;
use Extend\Warranty\Model\Product\Type as WarrantyType;
use Extend\Warranty\Model\CreateContract as WarrantyContractCreate;
use Magento\Framework\Event\ObserverInterface;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Store\Model\ScopeInterface;
use Extend\Warranty\Model\Config\Source\Event as CreateContractEvent;
use Psr\Log\LoggerInterface;

/**
 * Class ShipmentObserver
 *
 * Class for creating warranty contract after shipment
 */
class ShipmentObserver implements ObserverInterface
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
     * @var WarrantyRelation
     */
    private $warrantyRelation;

    /**
     * @param WarrantyContractCreate $warrantyContractCreate
     * @param DataHelper $dataHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        WarrantyContractCreate $warrantyContractCreate,
        DataHelper             $dataHelper,
        WarrantyRelation       $warrantyRelation,
        LoggerInterface        $logger
    )
    {
        $this->warrantyContractCreate = $warrantyContractCreate;
        $this->warrantyRelation = $warrantyRelation;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
    }

    /**
     * Create warranty contract for order item if item is shipped
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $shipment = $event->getShipment();
        /** @var OrderInterface $order */
        $order = $shipment->getOrder();

        $storeId = $order->getStoreId();
        $contractCreateEvent = $this->dataHelper->getContractCreateEvent(ScopeInterface::SCOPE_STORES, $storeId);

        if ($this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId)
            && $this->dataHelper->isWarrantyContractEnabled($storeId)
            && ($contractCreateEvent == CreateContractEvent::SHIPMENT_CREATE)
        ) {
            /** @var ShipmentItemInterface $shipmentItem */
            foreach ($shipment->getAllItems() as $shipmentItem) {
                $orderItems = [];
                $qtyShipped = [];

                foreach ($order->getItems() as $orderWarrantyItem) {
                    if ($orderWarrantyItem->getProductType() !== WarrantyType::TYPE_CODE) {
                        continue;
                    }

                    if ($orderWarrantyItem->getContractId() !== null) {
                        $contractCnt = count(json_decode($orderWarrantyItem->getContractId(), true));
                        if ($contractCnt == $orderWarrantyItem->getQtyOrdered()) {
                            continue;
                        }
                    }

                    $orderItem = $order->getItemById($shipmentItem->getOrderItemId());

                    if ($this->warrantyRelation->isWarrantyRelatedToOrderItem($orderWarrantyItem, $orderItem)) {
                        if ($orderWarrantyItem->getQtyOrdered() < $shipmentItem->getQty()) {
                            $orderItems[] = $orderWarrantyItem;
                            $qtyShipped[$orderWarrantyItem->getId()] = (int)$orderWarrantyItem->getQtyOrdered();
                        } else {
                            $orderItems[] = $orderWarrantyItem;
                            $qtyShipped[$orderWarrantyItem->getId()] = (int)$shipmentItem->getQty();
                            break;

                        }
                    }

                }

                foreach ($orderItems as $orderItem) {
                    if (!$this->dataHelper->isContractCreateModeScheduled($storeId)) {
                        try {
                            $this->warrantyContractCreate->createContract($order, $orderItem, $qtyShipped[$orderItem->getId()], $storeId);
                        } catch (LocalizedException $exception) {
                            $this->warrantyContractCreate->addContractToQueue($orderItem, $qtyShipped[$orderItem->getId()]);
                            $this->logger->error(
                                'Error during shipment event warranty contract creation. ' . $exception->getMessage()
                            );
                        }
                    } else {
                        try {
                            $this->warrantyContractCreate->addContractToQueue($orderItem, $qtyShipped[$orderItem->getId()]);
                        } catch (LocalizedException $exception) {
                            $this->logger->error($exception->getMessage());
                        }
                    }
                }
            }
        }
    }
}
