<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

declare(strict_types=1);

namespace Extend\Warranty\Model\Orders;

use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrdersCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;
use Extend\Warranty\Api\SyncInterface;
use Extend\Warranty\Helper\Api\Data as ExtendHelper;

/**
 * Class Sync historical orders
 */
class HistoricalOrdersSync implements SyncInterface
{
    private const EXTEND_HISTORICAL_ORDERS_TABLE_NAME = 'extend_historical_orders';

    /**
     * @var OrdersCollectionFactory
     */
    private $ordersCollectionFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Date
     */
    private $date;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * Batch size
     *
     * @var int
     */
    private $batchSize;

    /**
     * @var ExtendHelper
     */
    private $extendHelper;

    /**
     * Count of batches
     *
     * @var int
     */
    private $countOfBatches = 0;

    /**
     * @param OrdersCollectionFactory $ordersCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param Date $date
     * @param DateTime $dateTime
     * @param ExtendHelper $extendHelper
     * @param int $batchSize
     */
    public function __construct(
        OrdersCollectionFactory      $ordersCollectionFactory,
        ResourceConnection           $resourceConnection,
        Date                         $date,
        DateTime                     $dateTime,
        ExtendHelper                 $extendHelper,
        int $batchSize = self::DEFAULT_BATCH_SIZE
    ) {
        $this->ordersCollectionFactory = $ordersCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->date = $date;
        $this->dateTime = $dateTime;
        $this->batchSize = $batchSize;
        $this->extendHelper = $extendHelper;
    }

    /**
     * Get batch size
     *
     * @return int
     */
    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    /**
     * @param int $batchNumber
     * @param array $filters
     * @return array
     */
    public function getItems(int $batchNumber = 1, array $filters = []): array
    {
        $batchSize = $this->getBatchSize();
        $orderCollection = $this->ordersCollectionFactory->create();
        $orderCollection->addAttributeToSelect('*');

        $orderCollection->getSelect()->joinLeft(
            ['historical' => $this->resourceConnection->getTableName(self::EXTEND_HISTORICAL_ORDERS_TABLE_NAME)],
            'historical.entity_id = main_table.entity_id',
            ['historical.was_sent']
        );

        foreach ($filters as $field => $value) {
            if ($field == OrderInterface::CREATED_AT) {
                $orderCollection->addFieldToFilter(OrderInterface::CREATED_AT, ['from' => $value]);
                continue;
            }
            $orderCollection->addFieldToFilter($field, $value);
        }

        $orderCollection->addFieldToFilter('historical.was_sent', ['null' => true]);
        $orderCollection->setPageSize($batchSize);
        $orderCollection->setCurPage($batchNumber);
        $orderCollection->load();

        $orderCollection->getSelect()->__toString();

        $this->setCountOfBatches($orderCollection->getTotalCount());

        return $orderCollection->getItems();
    }

    /**
     * Get count of batches to process
     *
     * @return int
     */
    public function getCountOfBatches(): int
    {
        return $this->countOfBatches;
    }

    /**
     * Set batch size
     *
     * @param int $batchSize
     */
    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    /**
     * Set count of batches to process
     *
     * @param int $countOfItems
     */
    public function setCountOfBatches(int $countOfItems): void
    {
        $batchSize = $this->getBatchSize();
        $this->countOfBatches = (int)ceil($countOfItems/$batchSize);
    }

    /**
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return void
     */
    public function setFromDate(string $scopeType,$scopeId): void
    {
        $twoYearsDatetime = 60*60*24*30*12*2; // 2 Years
        $from = $this->dateTime->formatDate($this->date->gmtTimestamp() - $twoYearsDatetime, false);
        $this->extendHelper->setHistoricalOrdersSyncPeriod($from, $scopeType,$scopeId);
    }

    /**
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return string
     */
    public function getFromDate(string $scopeType,$scopeId): string
    {
        return $this->extendHelper->getHistoricalOrdersSyncPeriod($scopeType,$scopeId);
    }
}
