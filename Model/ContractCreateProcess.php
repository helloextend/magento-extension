<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model;

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Config\Source\CreateContractApi;
use Extend\Warranty\Model\ResourceModel\ContractCreate as ContractCreateResource;
use Extend\Warranty\Model\Orders as ExtendOrdersAPI;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Class ContractCreateProcess
 *
 * ContractCreateProcess Model
 */
class ContractCreateProcess
{
    /**
     * Contract Create Resource Model
     *
     * @var ContractCreateResource
     */
    private $contractCreateResource;

    /**
     * Date Time Model
     *
     * @var DateTime
     */
    private $dateTime;

    /**
     * DateTime
     *
     * @var Date
     */
    private $date;

    /**
     * Warranty Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Warranty Contract Model
     *
     * @var WarrantyContract
     */
    private $warrantyContract;

    /**
     * Order Item Repository Model
     *
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * Order Repository Model
     *
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * ExtendOrdersAPI Model
     *
     * @var ExtendOrdersAPI
     */
    private $extendOrdersApi;

    /**
     * Logger Model
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ExtendOrderRepository
     */
    protected $extendOrderRepository;

    /**
     * ContractCreateProcess constructor
     *
     * @param ContractCreateResource $contractCreateResource
     * @param DateTime $dateTime
     * @param Date $date
     * @param DataHelper $dataHelper
     * @param WarrantyContract $warrantyContract
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param ExtendOrdersAPI $extendOrdersApi
     * @param ExtendOrderRepository $extendOrderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ContractCreateResource $contractCreateResource,
        DateTime $dateTime,
        Date $date,
        DataHelper $dataHelper,
        WarrantyContract $warrantyContract,
        OrderItemRepositoryInterface $orderItemRepository,
        OrderRepositoryInterface $orderRepository,
        ExtendOrdersAPI $extendOrdersApi,
        ExtendOrderRepository $extendOrderRepository,
        LoggerInterface $logger
    ) {
        $this->contractCreateResource = $contractCreateResource;
        $this->dateTime = $dateTime;
        $this->date = $date;
        $this->dataHelper = $dataHelper;
        $this->warrantyContract = $warrantyContract;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderRepository = $orderRepository;
        $this->extendOrdersApi = $extendOrdersApi;
        $this->extendOrderRepository = $extendOrderRepository;
        $this->logger = $logger;
    }

    /**
     * Process records
     */
    public function execute()
    {

        $this->createScheduledOrders();
        $this->createScheduledContracts();

        $this->purgeOldContractCreateRecords();
    }

    private function createScheduledContracts()
    {
        $batchSize = $this->dataHelper->getContractsBatchSize();
        $offset = 0;

        $contractCreateRecords = $this->getContractCreateRecords();

        do {
            $contractCreateRecordsBatch = array_slice($contractCreateRecords, $offset, $batchSize);
            $batchCount = count($contractCreateRecordsBatch);

            $processedContractCreateRecords = [];
            foreach ($contractCreateRecordsBatch as $contractCreateRecord) {
                $recordId = $contractCreateRecord['id'];
                $qty = (int)$contractCreateRecord['qty'];

                $orderItem = $this->getOrderItem((int)$contractCreateRecord['order_item_id']);
                $order = $this->getOrder((int)$contractCreateRecord['order_id']);

                if (!$orderItem || !$order || $order->getId() != $orderItem->getOrderId()) {
                    $processedContractCreateRecords[$recordId] = ContractCreate::STATUS_FAILED;
                    continue;
                }

                try {
                    if ($this->dataHelper->getContractCreateApi() == CreateContractApi::ORDERS_API) {
                        $processedContractCreateRecords[$recordId] =
                            $this->extendOrdersApi->updateOrderItemStatus($order, $orderItem, $qty);
                    }
                } catch (LocalizedException $exception) {
                    $processedContractCreateRecords[$recordId] = ContractCreate::STATUS_FAILED;
                    $this->logger->error($exception->getMessage());
                }
            }

            $this->updateContractCreateRecords($processedContractCreateRecords);
            $offset += $batchSize;
        } while ($batchCount == $batchSize);
    }

    private function createScheduledOrders()
    {
        $offset = 0;
        $batchSize = $this->dataHelper->getContractsBatchSize();
        $orderCreateRecords = $this->getOrdersCreateRecords();

        do {
            $ordersCreateBatch = array_slice($orderCreateRecords, $offset, $batchSize);
            $batchCount = count($ordersCreateBatch);

            $processedContractCreateRecords = [];
            foreach ($ordersCreateBatch as $ordersCreateRecord) {
                $recordId = $ordersCreateRecord['id'];
                $order = $this->getOrder((int)$ordersCreateRecord['order_id']);

                if (!$order) {
                    $processedContractCreateRecords[$recordId] = ContractCreate::STATUS_FAILED;
                    continue;
                }

                try {
                    if ($this->dataHelper->getContractCreateApi() == CreateContractApi::ORDERS_API) {
                        try {
                            $extendOrder = $this->extendOrderRepository->get($order->getId());
                        } catch (NoSuchEntityException $e) {
                            $extendOrder = false;
                        }

                        if ($order->getState() != Order::STATE_CANCELED
                            && (!$extendOrder || !$extendOrder->getExtendOrderId())) {
                            /**
                             * create order only no extend order was created
                             */
                            $this->extendOrdersApi->create($order);
                        }

                        if ($order->getState() == Order::STATE_CANCELED
                            && $extendOrder
                            && $extendOrder->getExtendOrderId()) {
                            /**
                             * we cancel order only if was already sent to extend
                             * and have state canceled
                             */
                            $this->extendOrdersApi->cancel($order);
                        }

                        $processedContractCreateRecords[$recordId] = ContractCreate::STATUS_SUCCESS;
                    }
                } catch (LocalizedException $exception) {
                    $processedContractCreateRecords[$recordId] = ContractCreate::STATUS_FAILED;
                    $this->logger->error($exception->getMessage());
                }
            }

            $this->updateContractCreateRecords($processedContractCreateRecords);
            $offset += $batchSize;
        } while ($batchCount == $batchSize);
    }

    /**
     * Get records
     *
     * @return array
     */
    protected function getContractCreateRecords(): array
    {
        $connection = $this->contractCreateResource->getConnection();
        $tableName = $connection->getTableName('extend_warranty_contract_create');

        $select = $connection->select();
        $select->from(
            $tableName,
            ['id', 'order_item_id', 'order_id', 'qty']
        );
        $select->where('status is null')
            ->where('order_item_id != ?', 0);

        return $connection->fetchAssoc($select);
    }

    /**
     * Get records
     *
     * @return array
     */
    protected function getOrdersCreateRecords(): array
    {
        $connection = $this->contractCreateResource->getConnection();
        $tableName = $connection->getTableName('extend_warranty_contract_create');

        $select = $connection->select();
        $select->from(
            $tableName,
            ['id', 'order_id']
        );
        $select->where('status is null')
            ->where('order_item_id = ?', 0);

        return $connection->fetchAssoc($select);
    }

    /**
     * Update records
     *
     * @param array $processedRecords
     */
    protected function updateContractCreateRecords(array $processedRecords)
    {
        $connection = $this->contractCreateResource->getConnection();
        $tableName = $connection->getTableName('extend_warranty_contract_create');

        foreach ($processedRecords as $id => $status) {
            $connection->update(
                $tableName,
                ['status' => $status],
                ['id = ?' => $id]
            );
        }
    }

    /**
     * Purge old records
     */
    protected function purgeOldContractCreateRecords()
    {
        $storagePeriod = $this->dataHelper->getStoragePeriod();
        if (!$storagePeriod) {
            $this->logger->error('The storage period is not set.');
            return;
        }

        $connection = $this->contractCreateResource->getConnection();

        $currentDate = $this->dateTime->formatDate($this->date->gmtTimestamp());
        $dateToPurge = $connection->getDateAddSql(
            $connection->quote($currentDate),
            -$storagePeriod,
            AdapterInterface::INTERVAL_DAY
        );

        $tableName = $connection->getTableName('extend_warranty_contract_create');

        $connection->delete(
            $tableName,
            ['created_at < ?' => $dateToPurge]
        );
    }

    /**
     * Get order item
     *
     * @param int $orderItemId
     * @return OrderItemInterface|null
     */
    protected function getOrderItem(int $orderItemId)
    {
        try {
            $orderItem = $this->orderItemRepository->get($orderItemId);
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
            $orderItem = null;
        }

        return $orderItem;
    }

    /**
     * Get order
     *
     * @param int $orderId
     * @return OrderInterface|null
     */
    protected function getOrder(int $orderId)
    {
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
            $order = null;
        }

        return $order;
    }
}
