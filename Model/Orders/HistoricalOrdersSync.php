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

namespace Extend\Warranty\Model\Orders;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Sales\Api\OrderRepositoryInterface;
use Extend\Warranty\Api\SyncInterface;


/**
 * Class Sync historical orders
 */
class HistoricalOrdersSync implements SyncInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Search Criteria Builder
     *
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

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
     * Count of batches
     *
     * @var int
     */
    private $countOfBatches = 0;

    /**
     * @var string
     */
    private $fromDate;

    /**
     * @var string
     */
    private $toDate;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param Date $date
     * @param DateTime $dateTime
     * @param int $batchSize
     */
    public function __construct(
        SearchCriteriaBuilder        $searchCriteriaBuilder,
        OrderRepositoryInterface     $orderRepository,
        Date                         $date,
        DateTime                     $dateTime,
        int $batchSize = self::DEFAULT_BATCH_SIZE
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->date = $date;
        $this->dateTime = $dateTime;
        $this->batchSize = $batchSize;
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
     * Get products
     *
     * @param int $batchNumber
     * @param array $filters
     * @return array
     */
    public function getItems(int $batchNumber = 1, array $filters = []): array
    {
        $batchSize = $this->getBatchSize();

        foreach ($filters as $field => $value) {
            $this->searchCriteriaBuilder->addFilter($field, $value);
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('created_at', $this->getFromDate(), 'gteq')
            ->addFilter('created_at', $this->getToDate(), 'lteq')
            ->create();
        $searchCriteria->setPageSize($batchSize);
        $searchCriteria->setCurrentPage($batchNumber);
        $searchResults = $this->orderRepository->getList($searchCriteria);

        $this->setCountOfBatches($searchResults->getTotalCount());

        return $searchResults->getItems();
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
     * @param string $from
     * @return void
     */
    public function setFromDate(string $from): void
    {
        $this->fromDate = $from;
    }

    /**
     * @return string
     */
    public function getFromDate(): string
    {
        return $this->fromDate;
    }

    /**
     * @param string $to
     * @return void
     */
    public function setToDate(string $to): void
    {
        $this->toDate = $to;
    }

    /**
     * @return string
     */
    public function getToDate(): string
    {
        return $this->toDate;
    }

    /**
     * @return string
     */
    public function getSyncPeriod(): string
    {
        $from = $this->getFromDate();
        $to = $this->getToDate();

        if (!$from) {
            $offset = 60*60*24*30*12*2; // 2 Years
            $this->setFromDate($this->dateTime->formatDate($this->date->gmtTimestamp() - $offset));
            $from = $this->getFromDate();
        }

        if (!$to) {
            $this->setToDate($this->dateTime->formatDate($this->date->gmtTimestamp()));
            $to = $this->getToDate();
        }

        return sprintf('Orders From %s to %s', $from, $to);
    }
}
