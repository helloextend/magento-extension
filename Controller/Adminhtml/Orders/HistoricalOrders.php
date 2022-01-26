<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Controller\Adminhtml\Orders;

use Extend\Warranty\Model\HistoricalOrdersSyncFlag;
use Extend\Warranty\Model\Orders\HistoricalOrdersSync;
use Extend\Warranty\Model\Api\Sync\Orders\HistoricalOrdersRequest;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Extend\Warranty\Helper\Api\Data as DataHelper;

class HistoricalOrders extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Extend_Warranty::product_manual_sync';

    /**
     * Status
     */
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_FAIL = 'FAIL';

    /**
     * Website ID filter
     */
    const WEBSITE_ID = 'website_id';

    /**
     * Flag Manager
     *
     * @var FlagManager
     */
    private $flagManager;

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
     * @var HistoricalOrdersSync
     */
    private $historicalOrdersSync;

    /**
     * @var HistoricalOrdersRequest
     */
    private $apiHistoricalOrdersModel;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $syncLogger;

    /**
     * Sync constructor
     *
     * @param Context $context
     * @param DataHelper $dataHelper
     * @param DateTime $dateTime
     * @param Date $date
     * @param FlagManager $flagManager
     * @param ProductSyncModel $productSyncModel
     * @param ApiProductModel $apiProductModel
     * @param LoggerInterface $logger
     * @param LoggerInterface $syncLogger
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        DateTime $dateTime,
        Date $date,
        FlagManager $flagManager,
        HistoricalOrdersSync $historicalOrdersSync,
        HistoricalOrdersRequest $historicalOrdersRequest,
        LoggerInterface $logger,
        LoggerInterface $syncLogger
    ) {
        parent::__construct($context);
        $this->flagManager = $flagManager;
        $this->dateTime = $dateTime;
        $this->date = $date;
        $this->dataHelper = $dataHelper;
        $this->historicalOrdersSync = $historicalOrdersSync;
        $this->apiHistoricalOrdersModel = $historicalOrdersRequest;
        $this->logger = $logger;
        $this->syncLogger = $syncLogger;
    }

    public function execute()
    {
        $request = $this->getRequest();
        $currentBatch = (int)$request->getParam('currentBatchesProcessed');

        if (!(bool)$this->flagManager->getFlagData(HistoricalOrdersSyncFlag::FLAG_NAME) || $currentBatch > 1) {
            if (!$this->flagManager->getFlagData(HistoricalOrdersSyncFlag::FLAG_NAME)) {
                $this->flagManager->saveFlag(HistoricalOrdersSyncFlag::FLAG_NAME, true);
            }

            $filters = [];
            $website = $request->getParam('website');
            $store = $request->getParam('store');
            if ($website) {
                $scopeType = ScopeInterface::SCOPE_WEBSITES;
                $scopeId = $website;
                $filters[self::WEBSITE_ID] = $website;
            } elseif ($store) {
                $scopeType = ScopeInterface::SCOPE_STORES;
                $scopeId = $store;
                $filters[OrderItemInterface::STORE_ID] = $store;
            } else {
                $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
                $scopeId = Store::DEFAULT_STORE_ID;
            }

            $apiUrl = $this->dataHelper->getApiUrl($scopeType, $scopeId);
            $apiStoreId = $this->dataHelper->getStoreId($scopeType, $scopeId);
            $apiKey = $this->dataHelper->getApiKey($scopeType, $scopeId);

            $this->apiHistoricalOrdersModel->setConfig($apiUrl, $apiStoreId, $apiKey);

//            $batchSize = $this->dataHelper->getProductsBatchSize($scopeType, $scopeId);
//Current orders API max batch size is 10
            $batchSize = 10;
            $this->historicalOrdersSync->setBatchSize($batchSize);

            $offset = 60*60*24*30*12*2; // 2 Years
            $this->historicalOrdersSync->setFromDate($this->dateTime->formatDate($this->date->gmtTimestamp() - $offset, false));
            $this->historicalOrdersSync->setToDate($this->dateTime->formatDate($this->date->gmtTimestamp(), false));

            $orders = $this->historicalOrdersSync->getItems($currentBatch, $filters);
            $countOfBathes = $this->historicalOrdersSync->getCountOfBatches();

            if (!empty($orders)) {
                try {
                    $this->apiHistoricalOrdersModel->create($orders, $currentBatch);
                    $data['status'] = self::STATUS_SUCCESS;
                } catch (LocalizedException $exception) {
                    $message = sprintf('Error found in orders batch %s. %s', $currentBatch, $exception->getMessage());
                    $this->syncLogger->error($message);
                    $data = [
                        'status'    => self::STATUS_FAIL,
                        'message'   => __($message),
                    ];
                }

                if ($currentBatch === $countOfBathes) {
                    $period = $this->historicalOrdersSync->getSyncPeriod();
                    $this->dataHelper->setHistoricalOrdersSyncPeriod($period, $scopeType, $scopeId);
                    $data['msg'] = $period;
                    $this->flagManager->deleteFlag(HistoricalOrdersSyncFlag::FLAG_NAME);
                }
            } else {
                $period = $this->historicalOrdersSync->getSyncPeriod();
                $this->dataHelper->setHistoricalOrdersSyncPeriod($period, $scopeType, $scopeId);
                $data['msg'] = $period;
                $this->flagManager->deleteFlag(HistoricalOrdersSyncFlag::FLAG_NAME);
            }

            $currentBatch++;
            $data['totalBatches'] = $countOfBathes;
            $data['currentBatchesProcessed'] = $currentBatch;
        } else {
            $data = [
                'status'    => self::STATUS_FAIL,
                'message'   => __('Orders sync has already started by another process.'),
            ];
        }

        $jsonResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $jsonResult->setData($data);

        return $jsonResult;
    }
}
