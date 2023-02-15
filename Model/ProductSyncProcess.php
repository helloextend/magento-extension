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

use Extend\Warranty\Api\SyncInterface as ProductSyncModel;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Api\Sync\Product\ProductsRequest as ApiProductModel;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Class ProductSyncProcess
 *
 * Warranty ProductSyncProcess Model
 */
class ProductSyncProcess
{
    /**
     * Store Manager Model
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Date Time Model
     *
     * @var DateTime
     */
    private $dateTime;

    /**
     * Date Model
     *
     * @var Date
     */
    private $date;

    /**
     * Warranty Api Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Product Sync
     *
     * @var ProductSyncModel
     */
    private $productSyncModel;

    /**
     * Api Product
     *
     * @var ApiProductModel
     */
    private $apiProductModel;

    /**
     * Logger Model
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Logger Model
     *
     * @var LoggerInterface
     */
    private $syncLogger;

    /**
     * ProductSyncProcess constructor
     *
     * @param StoreManagerInterface $storeManager
     * @param DateTime $dateTime
     * @param Date $date
     * @param DataHelper $dataHelper
     * @param ProductSyncModel $productSyncModel
     * @param ApiProductModel $apiProductModel
     * @param LoggerInterface $logger
     * @param LoggerInterface $syncLogger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DateTime              $dateTime,
        Date                  $date,
        DataHelper            $dataHelper,
        ProductSyncModel      $productSyncModel,
        ApiProductModel       $apiProductModel,
        LoggerInterface       $logger,
        LoggerInterface       $syncLogger
    )
    {
        $this->storeManager = $storeManager;
        $this->date = $date;
        $this->dateTime = $dateTime;
        $this->dataHelper = $dataHelper;
        $this->productSyncModel = $productSyncModel;
        $this->apiProductModel = $apiProductModel;
        $this->logger = $logger;
        $this->syncLogger = $syncLogger;
    }

    /**
     * Execute sync for products
     *
     * @param int|null $defaultBatchSize
     * @return void
     */
    public function execute(int $defaultBatchSize = null)
    {
        $stores = $this->storeManager->getStores();
        foreach ($stores as $storeId => $store) {
            if (!$this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId)) {
                continue;
            }

            $storeCode = $store->getCode();
            $this->syncLogger->info(sprintf('Start sync products for %s store.', $storeCode));

            $apiUrl = $this->dataHelper->getApiUrl(ScopeInterface::SCOPE_STORES, $storeId);
            $apiStoreId = $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId);
            $apiKey = $this->dataHelper->getApiKey(ScopeInterface::SCOPE_STORES, $storeId);

            try {
                $this->apiProductModel->setConfig($apiUrl, $apiStoreId, $apiKey);
            } catch (Exception $exception) {
                $this->syncLogger->error($exception->getMessage());
                continue;
            }

            $batchSize = $defaultBatchSize ?:
                $this->dataHelper->getProductsBatchSize(ScopeInterface::SCOPE_STORES, $storeId);
            $this->productSyncModel->setBatchSize($batchSize);

            $filters[Product::STORE_ID] = $storeId;

            $currentDate = $this->dateTime->formatDate($this->date->gmtTimestamp());
            $lastSyncDate = $this->dataHelper->getLastProductSyncDate(ScopeInterface::SCOPE_STORES, $storeId);
            if ($lastSyncDate) {
                $filters[ProductInterface::UPDATED_AT] = $lastSyncDate;
            }

            $currentBatch = $batchNumber ?? 1;
            $countOfBathes = $batchNumber ?? $this->productSyncModel->getCountOfBatches();

            do {
                $this->syncProducts($storeId, $currentBatch, $filters);
                $currentBatch++;
            } while ($currentBatch <= $countOfBathes);

            $this->dataHelper->setLastProductSyncDate($currentDate, ScopeInterface::SCOPE_STORES, $storeId);
            $this->syncLogger->info(sprintf('Finish sync products for %s store.', $storeCode));
        }
    }

    /**
     * @param $storeId
     * @param $currentBatch
     * @param $filters
     * @return void
     */
    public function syncProducts($storeId, $currentBatch, $filters)
    {
        $products = $this->productSyncModel->getItems($currentBatch, $filters);
        if (!empty($products)) {
            try {
                $this->apiProductModel->create($products, $currentBatch, ScopeInterface::SCOPE_STORES, $storeId);
            } catch (LocalizedException $exception) {
                $this->syncLogger->info(sprintf(
                    'Error found in products batch %s. %s',
                    $currentBatch,
                    $exception->getMessage()
                ));
            }
        } else {
            $this->syncLogger->info(sprintf(
                'Nothing to sync in batch %s.',
                $currentBatch
            ));
        }
    }
}
