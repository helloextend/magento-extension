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

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Api\Sync\Orders\HistoricalOrdersRequest;
use Extend\Warranty\Model\HistoricalOrderFactory;
use Extend\Warranty\Model\ResourceModel\HistoricalOrder as HistoricalOrderModelResource;
use Extend\Warranty\Model\HistoricalOrdersSyncFlag;
use Extend\Warranty\Model\Orders\HistoricalOrdersSync;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\FlagManager;
use Extend\Warranty\Model\GetAfterDate;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

class HistoricalOrders extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Extend_Warranty::orders_manual_sync';

    /**
     * Status
     */
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_FAIL = 'FAIL';
    const STATUS_COMPLETE = 'COMPLETE';

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
     * @var GetAfterDate
     */
    private $getAfterDate;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Historical Orders Sync
     *
     * @var HistoricalOrdersSync
     */
    private $historicalOrdersSync;

    /**
     * Historical Orders API Model
     *
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
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * Historical Order Factory
     *
     * @var HistoricalOrderFactory
     */
    private $historicalOrderFactory;

    /**
     * Historical Order Model Resource
     *
     * @var HistoricalOrderModelResource
     */
    private $historicalOrderResource;

    /**
     * HistoricalOrders constructor
     *
     * @param Context $context
     * @param DataHelper $dataHelper
     * @param GetAfterDate $getAfterDate
     * @param FlagManager $flagManager
     * @param HistoricalOrdersSync $historicalOrdersSync
     * @param HistoricalOrdersRequest $historicalOrdersRequest
     * @param LoggerInterface $logger
     * @param LoggerInterface $syncLogger
     * @param HistoricalOrderFactory $historicalOrderFactory
     * @param HistoricalOrderModelResource $historicalOrderResource
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        GetAfterDate $getAfterDate,
        FlagManager $flagManager,
        HistoricalOrdersSync $historicalOrdersSync,
        HistoricalOrdersRequest $historicalOrdersRequest,
        LoggerInterface $logger,
        LoggerInterface $syncLogger,
        HistoricalOrderFactory $historicalOrderFactory,
        HistoricalOrderModelResource $historicalOrderResource
    ) {
        parent::__construct($context);
        $this->flagManager = $flagManager;
        $this->getAfterDate = $getAfterDate;
        $this->dataHelper = $dataHelper;
        $this->historicalOrdersSync = $historicalOrdersSync;
        $this->apiHistoricalOrdersModel = $historicalOrdersRequest;
        $this->logger = $logger;
        $this->syncLogger = $syncLogger;
        $this->messageManager = $context->getMessageManager();
        $this->historicalOrderFactory = $historicalOrderFactory;
        $this->historicalOrderResource = $historicalOrderResource;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     */
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

            $batchSize = $this->dataHelper->getHistoricalOrdersBatchSize($scopeType, $scopeId);

            $this->historicalOrdersSync->setBatchSize($batchSize);

            if(!$this->dataHelper->getHistoricalOrdersSyncPeriod($scopeType, $scopeId)) {
                $date = $this->getAfterDate->getAfterDateTwoYears();
                $this->dataHelper->setHistoricalOrdersSyncPeriod($date, $scopeType, $scopeId);
            }

            $fromDate = $this->dataHelper->getHistoricalOrdersSyncPeriod($scopeType, $scopeId);
            $filters['created_at'] = $fromDate;

            $orders = $this->historicalOrdersSync->getItems($currentBatch, $filters);
            $countOfBathes = $this->historicalOrdersSync->getCountOfBatches();

            if (!empty($orders)) {
                try {
                    $sendResult = $this->apiHistoricalOrdersModel->create($orders, $currentBatch);
                    if ($sendResult) {
                        $this->trackHistoricalOrders($orders);
                    }
                    $data['status'] = self::STATUS_SUCCESS;
                } catch (LocalizedException $exception) {
                    $message = sprintf('Error found in orders batch %s. %s', $currentBatch, $exception->getMessage());
                    $this->syncLogger->error($message);
                    $this->flagManager->deleteFlag(HistoricalOrdersSyncFlag::FLAG_NAME);
                    $data = [
                        'status'    => self::STATUS_FAIL,
                        'message'   => __($message),
                    ];
                }

                if ($currentBatch === $countOfBathes) {
                    $this->flagManager->deleteFlag(HistoricalOrdersSyncFlag::FLAG_NAME);
                }
            } else {
                $this->messageManager->addErrorMessage('Production orders have already been integrated to Extend.  The historical import has been canceled.');
                $this->syncLogger->info('Production orders have already been integrated to Extend.  The historical import has been canceled.');
                $data = [
                    'status'    => self::STATUS_COMPLETE,
                    'message'   => __('Production orders have already been integrated to Extend.  The historical import has been canceled.'),
                ];
                $this->flagManager->deleteFlag(HistoricalOrdersSyncFlag::FLAG_NAME);
            }

            $currentBatch++;
            $data['totalBatches'] = $countOfBathes;
            $data['currentBatchesProcessed'] = $currentBatch;
        } else {
            $this->syncLogger->info(__('Orders sync has already started by another process.'));
            $this->flagManager->deleteFlag(HistoricalOrdersSyncFlag::FLAG_NAME);
            $data = [
                'status'    => self::STATUS_FAIL,
                'message'   => __('Orders sync has already started by another process.'),
            ];
        }

        $jsonResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $jsonResult->setData($data);

        return $jsonResult;
    }

    /**
     * @param array $orders
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function trackHistoricalOrders(array $orders)
    {
        $historicalOrder = $this->historicalOrderFactory->create();
        foreach ($orders as $order) {
            $historicalOrder->setEntityId($order->getId());
            $historicalOrder->setWasSent(true);
            $this->historicalOrderResource->save($historicalOrder);
        }
    }
}
