<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Controller\Adminhtml\Products;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Extend\Warranty\Model\SyncProcess;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Psr\Log\LoggerInterface;
use Extend\Warranty\Api\SyncInterface;
use Exception;

/**
 * Class Sync
 */
class Sync extends Action
{
    /**
     * Status codes
     */
    const STATUS_CODE_OK = 200;

    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Extend_Warranty::product_manual_sync';

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Sync Interface
     *
     * @var SyncInterface
     */
    private $sync;

    /**
     * Sync Process
     *
     * @var SyncProcess
     */
    private $syncProcess;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

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
     * Total batches
     *
     * @var int
     */
    private $totalBatches;

    /**
     * Batch size
     *
     * @var int
     */
    private $batchSize;

    /**
     * Reset total
     *
     * @var bool
     */
    private $resetTotal;

    /**
     * Sync constructor
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param SyncProcess $syncProcess
     * @param SyncInterface $sync
     * @param DataHelper $dataHelper
     * @param DateTime $dateTime
     * @param Date $date
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        SyncProcess $syncProcess,
        SyncInterface $sync,
        DataHelper $dataHelper,
        DateTime $dateTime,
        Date $date
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->syncProcess = $syncProcess;
        $this->sync = $sync;
        $this->dataHelper = $dataHelper;
        $this->dateTime = $dateTime;
        $this->date = $date;
    }

    /**
     * Sync products
     *
     * @return Json
     */
    public function execute(): Json
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        $currentBatch = (int)$params['currentBatchesProcessed'] ?? 0;

        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeId = 0;
        if (isset($params['scope'])) {
            $scope = $params['scope'];
            $scopeId = $params['scopeId'];
        }

        if ($this->batchSize === null) {
            $batchSize = $this->dataHelper->getProductsBatchSize($scope, $scopeId);

            if ($batchSize) {
                $this->batchSize = $batchSize;
                $this->sync->setBatchSize($batchSize);
            }
        }

        if ($this->totalBatches === null) {
            $this->totalBatches = $this->sync->getBatchesToProcess();
            $this->resetTotal = false;
        }

        $productsBatch = $this->sync->getProducts($currentBatch);

        $data = [];

        try {
            $this->syncProcess->sync($productsBatch, $currentBatch);
            $data['status'] = 'SUCCESS';
        } catch (Exception $e) {
            $this->logger->info('Error found in products batch ' . $currentBatch, ['Exception' => $e->getMessage()]);
            $data['status'] = 'FAIL';
        }

        if ($currentBatch === $this->totalBatches) {
            $currentDate = $this->dateTime->formatDate($this->date->gmtTimestamp());
            $this->dataHelper->setLastProductSyncDate($currentDate, $scope, $scopeId);
            $data['msg'] = $currentDate;
            $this->resetTotal = true;
        }

        $currentBatch++;

        $data['totalBatches'] = $this->totalBatches;
        $data['currentBatchesProcessed'] = $currentBatch;

        if ($this->resetTotal) {
            unset($this->totalBatches);
        }

        $jsonResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $jsonResult->setHttpResponseCode(self::STATUS_CODE_OK);
        $jsonResult->setData($data);

        return $jsonResult;
    }
}
