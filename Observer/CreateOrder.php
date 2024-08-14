<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Observer;

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Config\Source\CreateContractApi;
use Extend\Warranty\Model\Orders as ExtendOrder;
use Extend\Warranty\Model\Product\Type;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Exception;
use Extend\Warranty\Model\CreateContract as WarrantyContractCreate;
use \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;

/**
 * Class CreateLead
 *
 * CreateLead Observer
 */
class CreateOrder implements ObserverInterface
{
    /**
     * Order Item Repository Model
     *
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

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

    /**
     * Warranty Contract Model
     *
     * @var WarrantyContractCreate
     */
    private $warrantyContractCreate;

    /**
     * OrderItemCollectionFactory
     *
     * @var Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    protected $orderItemCollectionFactory;

    /**
     * CreateLead constructor
     *
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param ExtendOrder $extendOrder
     * @param DataHelper $dataHelper
     * @param LoggerInterface $logger
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory
     */
    public function __construct(
        OrderItemRepositoryInterface $orderItemRepository,
        ExtendOrder                  $extendOrder,
        DataHelper                   $dataHelper,
        LoggerInterface              $logger,
        WarrantyContractCreate       $warrantyContractCreate,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory                        $orderItemCollectionFactory
    )
    {
        $this->orderItemRepository = $orderItemRepository;
        $this->extendOrder = $extendOrder;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
        $this->warrantyContractCreate = $warrantyContractCreate;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;

    }

    /**
     * Create an order on Extend Side
     *
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();

        /** @var OrderInterface $order */
        $order = $event->getOrder();
        $storeId = $order->getStoreId();

        $contractCreateApi = $this->dataHelper->getContractCreateApi(
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        if (
            !$this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId)
            || $contractCreateApi != CreateContractApi::ORDERS_API
        ) {
            return;
        }


        /** @var OrderItemInterface $orderItem */
        foreach ($order->getAllItems() as $orderItem) {
            $orderItem->setOrder($order);

            if ($orderItem->getProductType() === Type::TYPE_CODE) {
                $this->processBuyRequestLeadToken($orderItem);
                $this->orderItemRepository->save($orderItem);
            }
        }

        if (!$this->dataHelper->isContractCreateModeScheduled($storeId)) {
            try {
                $this->extendOrder->create($order);
            } catch (LocalizedException $e) {
                $this->warrantyContractCreate->addOrderToQueue($order);
                $this->logger->critical($e);
            }
        } else {
            $this->warrantyContractCreate->addOrderToQueue($order);
        }
    }

    /**
     * Saving lead token to warranty order item
     * from buy request so it can be connected to
     * warrantable order item later.
     *
     * This code can be moved to plugin
     * beforeOrderItem save to move lead tokens
     * from buy request to leadToken field.
     *
     * @param OrderItemInterface $warrantyItem
     */
    private function processBuyRequestLeadToken(OrderItemInterface $warrantyItem)
    {
        try {
            if (array_key_exists('leadToken', $warrantyItem->getProductOptionByCode('info_buyRequest'))) {
                $leadToken[] = $warrantyItem->getProductOptionByCode('info_buyRequest')['leadToken'];
                if (!empty($leadToken)) {
                    $warrantyItem->setLeadToken(json_encode($leadToken));

                    // Set parent order id by finding an existing sales_order_item record with same lead_token  and type not warranty
                    $orderItemCollectionCreateOrder = $this->orderItemCollectionFactory->create();
                    $orderItemCollectionCreateOrder->addFieldToFilter('product_type', ['neq' => 'warranty']);
                    $orderItemCollectionCreateOrder->addFieldToFilter('lead_token', ['like' => '%'.str_replace('"', "",  $leadToken[0]).'%' ] );
                    $existingOrderItem = $orderItemCollectionCreateOrder->getFirstItem();
                    if ($existingOrderItem && $existingOrderItem->getId()) {
                        $parentOrderId = $existingOrderItem->getOrderId();
                        $warrantyItem->setExtendParentOrderId($parentOrderId);
                    }
                }
            }
        } catch (Exception $exception) {
            $this->logger->error('Error during lead saving. ' . $exception->getMessage());
        }
    }
}
