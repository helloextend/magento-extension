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

namespace Extend\Warranty\Model;

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Config\Source\CreateContractApi;
use Extend\Warranty\Model\ResourceModel\ContractCreate as ContractCreateResource;
use Extend\Warranty\Model\Orders as ExtendOrdersAPI;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ContractCreateProcess
 */
class ContractCreateProcess
{
    /**
     * Contract Create Resource
     *
     * @var ContractCreateResource
     */
    private $contractCreateResource;

    /**
     * Date Time
     *
     * @var DateTime
     */
    private $dateTime;

    /**
     * Date
     *
     * @var Date
     */
    private $date;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Warranty Contract
     *
     * @var WarrantyContract
     */
    private $warrantyContract;

    /**
     * Order Item Repository Interface
     *
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * Order Repository Interface
     *
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ExtendOrdersAPI
     */
    private $extendOrdersApi;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

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
        $this->logger = $logger;
    }

    /**
     * Process records
     */
    public function execute()
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
                $orderItem = $this->getOrderItem((int)$contractCreateRecord[InvoiceItemInterface::ORDER_ITEM_ID]);
                if (!$orderItem) {
                    $processedContractCreateRecords[$recordId] = ContractCreate::STATUS_FAILED;
                    continue;
                }

                $orderId = (int)$orderItem->getOrderId();
                $order = $this->getOrder($orderId);

                if (!$order) {
                    $processedContractCreateRecords[$recordId] = ContractCreate::STATUS_FAILED;
                    continue;
                }

                $qtyInvoiced = intval($contractCreateRecord[OrderItemInterface::QTY_INVOICED]);

                try {
                    if ($this->dataHelper->getContractCreateApi() == CreateContractApi::ORDERS_API && $this->dataHelper->isContractCreateModeScheduled()) {
                        $processedContractCreateRecords[$recordId] = $this->extendOrdersApi->createOrder($order, $orderItem, $qtyInvoiced);
                    } else {
                        $processedContractCreateRecords[$recordId] = $this->warrantyContract->create($order, $orderItem, $qtyInvoiced);
                    }
                } catch (LocalizedException $exception) {
                    $processedContractCreateRecords[$recordId] = ContractCreate::STATUS_FAILED;
                    $this->logger->error($exception->getMessage());
                }
            }

            $this->updateContractCreateRecords($processedContractCreateRecords);
            $offset += $batchSize;
        } while ($batchCount == $batchSize);

        $this->purgeOldContractCreateRecords();
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
            ['id', 'order_item_id', 'qty_invoiced']
        );
        $select->where('status is null');

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
