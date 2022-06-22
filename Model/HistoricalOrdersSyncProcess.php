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

namespace Extend\Warranty\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Api\Data\HistoricalOrderInterfaceFactory;
use Extend\Warranty\Model\ResourceModel\HistoricalOrder as HistoricalOrderResource;
use Extend\Warranty\Model\Api\Sync\Orders\HistoricalOrdersRequest;
use Extend\Warranty\Model\GetAfterDate;
use Extend\Warranty\Model\Orders\HistoricalOrdersSync;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Class SendHistoricalOrders
 */
class HistoricalOrdersSyncProcess
{
    /**
     * Store Manager
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Extend Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Historical Order Factory
     *
     * @var HistoricalOrderInterfaceFactory
     */
    private $historicalOrderFactory;

    /**
     * Historical Order API Model
     *
     * @var HistoricalOrdersRequest
     */
    private $apiHistoricalOrdersModel;

    /**
     * Historical Orders Sync
     *
     * @var HistoricalOrdersSync
     */
    private $historicalOrdersSync;

    /**
     * Historical Order Resource
     *
     * @var HistoricalOrderResource
     */
    private $historicalOrderResource;

    /**
     * Get Sync From Date
     *
     * @var \Extend\Warranty\Model\GetAfterDate
     */
    private $getAfterDate;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Sync Logger
     *
     * @var LoggerInterface
     */
    private $syncLogger;

    /**
     * @param StoreManagerInterface $storeManager
     * @param DataHelper $dataHelper
     * @param HistoricalOrderInterfaceFactory $historicalOrderFactory
     * @param HistoricalOrdersRequest $historicalOrdersRequest
     * @param HistoricalOrdersSync $historicalOrdersSync
     * @param HistoricalOrderResource $historicalOrderResource
     * @param \Extend\Warranty\Model\GetAfterDate $getAfterDate
     * @param LoggerInterface $logger
     * @param LoggerInterface $syncLogger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DataHelper $dataHelper,
        HistoricalOrderInterfaceFactory $historicalOrderFactory,
        HistoricalOrdersRequest $historicalOrdersRequest,
        HistoricalOrdersSync $historicalOrdersSync,
        HistoricalOrderResource $historicalOrderResource,
        GetAfterDate $getAfterDate,
        LoggerInterface $logger,
        LoggerInterface $syncLogger
    ) {
        $this->storeManager = $storeManager;
        $this->dataHelper = $dataHelper;
        $this->historicalOrderFactory = $historicalOrderFactory;
        $this->apiHistoricalOrdersModel = $historicalOrdersRequest;
        $this->historicalOrdersSync = $historicalOrdersSync;
        $this->historicalOrderResource = $historicalOrderResource;
        $this->getAfterDate = $getAfterDate;
        $this->logger = $logger;
        $this->syncLogger = $syncLogger;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $stores = $this->storeManager->getStores();
        foreach ($stores as $storeId => $store) {
            if (!$this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId)) {
                continue;
            }

            $storeCode = $store->getCode();
            $this->syncLogger->info(sprintf('Start sync historical orders for %s store.', $storeCode));

            $apiUrl = $this->dataHelper->getApiUrl(ScopeInterface::SCOPE_STORES, $storeId);
            $apiStoreId = $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId);
            $apiKey = $this->dataHelper->getApiKey(ScopeInterface::SCOPE_STORES, $storeId);

            try {
                $this->apiHistoricalOrdersModel->setConfig($apiUrl, $apiStoreId, $apiKey);
            } catch (Exception $exception) {
                $this->syncLogger->error($exception->getMessage());
                continue;
            }

            if(!$this->dataHelper->getHistoricalOrdersSyncPeriod(ScopeInterface::SCOPE_STORES, $storeId)) {
                $date = $this->getAfterDate->getAfterDateTwoYears();
                $this->dataHelper->setHistoricalOrdersSyncPeriod($date,ScopeInterface::SCOPE_STORES, $storeId);
                $this->dataHelper->setHistoricalOrdersSyncPeriod($date,ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            }

            $batchSize = $this->dataHelper->getHistoricalOrdersBatchSize(ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
            $this->historicalOrdersSync->setBatchSize($batchSize);

            $fromDate = $this->dataHelper->getHistoricalOrdersSyncPeriod(ScopeInterface::SCOPE_STORES, $storeId);

            $currentBatch = 1;
            $filters['store_id'] = $storeId;
            $filters['created_at'] = $fromDate;

            $historicalOrders = $this->historicalOrdersSync->getItems($currentBatch, $filters);
            $countOfBathes = $this->historicalOrdersSync->getCountOfBatches();

            do {
                if (!empty($historicalOrders)) {
                    try {
                        $sendResult = $this->apiHistoricalOrdersModel->create($historicalOrders, $currentBatch);
                        if ($sendResult) {
                            $this->syncLogger->error('Before track');
                            $this->trackHistoricalOrders($historicalOrders);
                            $this->syncLogger->error('After Track');
                        }
                    } catch (LocalizedException $exception) {
                        $message = sprintf('Error found in historical orders batch %s. %s', $currentBatch, $exception->getMessage());
                        $this->syncLogger->error($message);
                    }

                    if ($currentBatch === $countOfBathes) {
                        $this->syncLogger->info('End cycle');
                    }
                } else {
                    $this->syncLogger->info('Production orders have already been integrated to Extend.  The historical import has been canceled.');
                }
                $currentBatch++;
                $historicalOrders = $this->historicalOrdersSync->getItems($currentBatch, $filters);
            } while($currentBatch <= $countOfBathes);

            $this->syncLogger->info(sprintf('Finish sync historical orders for %s store.', $storeCode));
        }
    }

    /**
     * @param array $orders
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function trackHistoricalOrders(array $orders)
    {
        $this->syncLogger->error('Before Track cycle');
        $historicalOrder = $this->historicalOrderFactory->create();
        foreach ($orders as $order) {
            $this->syncLogger->error('Track cycle' . $order->getId());
            $historicalOrder->setEntityId($order->getId());
            $historicalOrder->setWasSent(true);
            $this->historicalOrderResource->save($historicalOrder);
        }
    }
}
