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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Api\Sync\Product\ProductsRequest as ApiProductModel;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class ProductSyncProcess
 */
class ProductSyncProcess
{
    /**
     * App State
     *
     * @var AppState
     */
    private $appState;

    /**
     * Product Repository Interface
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Search Criteria Builder
     *
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Api Product Model
     *
     * @var ApiProductModel
     */
    private $apiProductModel;

    /**
     * DateTime
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
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ProductSyncProcess constructor
     *
     * @param AppState $appState
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DataHelper $dataHelper
     * @param ApiProductModel $apiProductModel
     * @param DateTime $dateTime
     * @param Date $date
     * @param LoggerInterface $logger
     */
    public function __construct(
        AppState $appState,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DataHelper $dataHelper,
        ApiProductModel $apiProductModel,
        DateTime $dateTime,
        Date $date,
        LoggerInterface $logger
    ) {
        $this->appState = $appState;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dataHelper = $dataHelper;
        $this->apiProductModel = $apiProductModel;
        $this->dateTime = $dateTime;
        $this->date = $date;
        $this->logger = $logger;
    }

    /**
     * Sync products
     */
    public function execute(): void
    {
        $lastSyncDate = $this->getLastSyncDate();
        $batchSize = $this->dataHelper->getProductsBatchSize();
        $currentBatch = 1;

        if ($lastSyncDate) {
            $this->searchCriteriaBuilder->addFilter(ProductInterface::UPDATED_AT, $lastSyncDate, 'gt');
        }
        $this->searchCriteriaBuilder->addFilter(ProductInterface::TYPE_ID, Type::TYPE_CODE, 'neq');
        $this->searchCriteriaBuilder->setCurrentPage($currentBatch);
        $this->searchCriteriaBuilder->setPageSize($batchSize);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResult = $this->productRepository->getList($searchCriteria);
        $totalCount = $searchResult->getTotalCount();

        while ($totalCount > 0) {
            try {
                $searchResult = $this->productRepository->getList($searchCriteria);
                $products = $searchResult->getItems();
                $this->apiProductModel->create($products, $currentBatch);
            } catch (LocalizedException $exception) {
                $this->logger->error('Error during product synchronization. ' . $exception->getMessage());
            }

            $searchCriteria->setCurrentPage(++$currentBatch);
            $totalCount = $totalCount - $batchSize;
        }

        $this->setLastSyncDate();
    }

    /**
     * Get last product sync date according to sync type
     *
     * @return string
     */
    protected function getLastSyncDate(): string
    {
        try {
            $lastSyncDate = $this->appState->getAreaCode() === Area::AREA_CRONTAB
                ? $this->dataHelper->getCronLastProductSyncDate()
                : $this->dataHelper->getLastProductSyncDate();
        } catch (LocalizedException $exception) {
            $lastSyncDate = $this->dataHelper->getLastProductSyncDate();
        }

        return $lastSyncDate;
    }

    /**
     * Set last product sync date according to sync type
     */
    protected function setLastSyncDate(): void
    {
        $currentDate = $this->dateTime->formatDate($this->date->gmtTimestamp());

        try {
            if ($this->appState->getAreaCode() === Area::AREA_CRONTAB) {
                $this->dataHelper->setCronLastProductSyncDate($currentDate);
            } else {
                $this->dataHelper->setLastProductSyncDate($currentDate);
            }
        } catch (LocalizedException $exception) {
            $this->dataHelper->setLastProductSyncDate($currentDate);
        }
    }
}
