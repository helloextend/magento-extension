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

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Config\Source\CreateContractApi;
use Extend\Warranty\Model\Leads as LeadModel;
use Extend\Warranty\Model\Offers as OfferModel;
use Extend\Warranty\Model\Orders as ExtendOrder;
use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Model\WarrantyRelation;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Exception;

/**
 * Class CreateLead
 *
 * CreateLead Observer
 */
class CreateLead implements ObserverInterface
{
    /**
     * Order Item Repository Model
     *
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * Offer
     *
     * @var OfferModel
     */
    private $offerModel;

    /**
     * Lead
     *
     * @var LeadModel
     */
    private $leadModel;

    /**
     * ExtendOrder Model
     *
     * @var ExtendOrder
     */
    private $extendOrder;

    /**
     * Warranty Api Data Helper
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

    protected $warrantyRelation;

    /**
     * CreateLead constructor
     *
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param OfferModel $offerModel
     * @param LeadModel $leadModel
     * @param ExtendOrder $extendOrder
     * @param DataHelper $dataHelper
     * @param LoggerInterface $logger
     * @param WarrantyRelation $warrantyRelation
     */
    public function __construct(
        OrderItemRepositoryInterface $orderItemRepository,
        OfferModel $offerModel,
        LeadModel $leadModel,
        ExtendOrder $extendOrder,
        DataHelper $dataHelper,
        LoggerInterface $logger,
        WarrantyRelation $warrantyRelation
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->offerModel = $offerModel;
        $this->leadModel = $leadModel;
        $this->extendOrder = $extendOrder;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
        $this->warrantyRelation = $warrantyRelation;
    }

    /**
     * Create a lead
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();

        /** @var OrderInterface $order */
        $order = $event->getOrder();
        $storeId = $order->getStoreId();

        if ($this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId)
            && $this->dataHelper->isLeadEnabled($storeId)
        ) {
            $productItems = $warrantyItems = [];

            /** @var OrderItemInterface $orderItem */
            foreach ($order->getAllItems() as $orderItem) {
                $orderItem->setOrder($order);
                if ($orderItem->getProductType() === Type::TYPE_CODE) {
                    $warrantyItems[] = $orderItem;
                } else {
                    $productItems[] = $orderItem;
                }
            }

            if (count($warrantyItems) > 0) {
                foreach ($warrantyItems as &$warrantyItem) {
                    $this->setLeadToken($warrantyItem);
                }
            }

            foreach ($productItems as &$productItem) {
                $hasWarranty = false;
                foreach ($warrantyItems as $warrantyItem) {
                    if($this->warrantyRelation->isWarrantyRelatedToOrderItem($warrantyItem, $productItem)){
                        $hasWarranty = true;
                    }
                }

                if ($hasWarranty) {
                    continue;
                }
                $contractCreateApi = $this->dataHelper->getContractCreateApi(
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                );

                if ($contractCreateApi === CreateContractApi::ORDERS_API) {
                    $this->saveLeadTokenForOrders($order, $productItem);
                }
            }
        }
    }

    /**
     * Set Lead Token
     *
     * @param OrderItemInterface $warrantyItem
     */
    private function setLeadToken(OrderItemInterface &$warrantyItem)
    {
        try {
            if (array_key_exists('leadToken', $warrantyItem->getProductOptionByCode('info_buyRequest'))) {
                $leadToken[] = $warrantyItem->getProductOptionByCode('info_buyRequest')['leadToken'];
                if (!empty($leadToken)) {
                    $warrantyItem->setLeadToken(json_encode($leadToken));
                }
            }
        } catch (Exception $exception) {
            $this->logger->error('Error during lead saving. ' . $exception->getMessage());
        }
    }

    /**
     * Set Lead Token Orders
     *
     * @param OrderInterface $order
     * @param OrderItemInterface $productItem
     */
    private function saveLeadTokenForOrders(OrderInterface $order, OrderItemInterface $productItem)
    {
        try {
            $leadToken = $this->extendOrder->createOrder(
                $order,
                $productItem,
                (int)$productItem->getQtyOrdered(),
                ExtendOrder::LEAD
            );
            if ($leadToken) {
                $productItem->setLeadToken($leadToken);
                if ($order->getId()) {
                    $this->orderItemRepository->save($productItem);
                }
            }
        } catch (LocalizedException $exception) {
            $this->logger->error('Error during lead creation. ' . $exception->getMessage());
        }
    }
}
