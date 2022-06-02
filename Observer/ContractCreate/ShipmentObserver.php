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
     * Create warranty contract for order item if item is shipped
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $shipment = $event->getShipment();
        $order = $shipment->getOrder();

        $storeId = $order->getStoreId();
        $contractCreateEvent = $this->dataHelper->getContractCreateEvent(ScopeInterface::SCOPE_STORES, $storeId);

        if ($this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId)
            && $this->dataHelper->isWarrantyContractEnabled($storeId)
            && ($contractCreateEvent == CreateContractEvent::SHIPMENT_CREATE)
        ) {
            foreach ($shipment->getAllItems() as $shipmentItem) {
                $orderItem = null;

                foreach ($order->getItems() as $orderWarrantyItem) {
                    if ($orderWarrantyItem->getProductType() !== WarrantyType::TYPE_CODE) {
                        continue;
                    }

                    if ($shipmentItem->getSku() == $orderWarrantyItem->getProductOptionByCode('associated_product')) {
                        $orderItem = $orderWarrantyItem;
                        break;
                    }

                }

                $qtyShipped = (int)$shipmentItem->getQty();

                if (!$this->dataHelper->isContractCreateModeScheduled(ScopeInterface::SCOPE_STORES, $storeId)) {
                    try {
                        $this->warrantyContractCreate->createContract($order, $orderItem, $qtyShipped, $storeId);
                    } catch (LocalizedException $exception) {
                        $this->warrantyContractCreate->addContactToQueue($orderItem, $qtyShipped);
                        $this->logger->error(
                            'Error during shipment event warranty contract creation. ' . $exception->getMessage()
                        );
                    }
                } else {
                    try {
                        $this->warrantyContractCreate->addContactToQueue($orderItem, $qtyShipped);
                    } catch (LocalizedException $exception) {
                        $this->logger->error($exception->getMessage());
                    }
                }
            }
        }
    }
}
