<?php

namespace Extend\Warranty\Cron;

use Extend\Warranty\Helper\Api\Data as Config;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Extend\Warranty\Api\SyncInterface;
use Extend\Warranty\Model\SyncProcess;
use Extend\Warranty\Api\TimeUpdaterInterface;
use Psr\Log\LoggerInterface;

class Sync
{
    const BATCH_SIZE_PATH = 'warranty/products/batch_size';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $extendConfig;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var int
     */
    protected $batchSize;

    /**
     * @var int
     */
    protected $totalBatches;


    /**
     * @var SyncProcess
     */
    protected $syncProcess;

    /**
     * @var SyncInterface
     */
    protected $sync;

    /**
     * @var bool
     */
    protected $resetTotal;

    /**
     * @var TimeUpdaterInterface
     */
    protected $timeUpdater;


    public function __construct
    (
        Config $extendConfig,
        ScopeConfigInterface $scopeConfig,
        SyncProcess $syncProcess,
        TimeUpdaterInterface $timeUpdater,
        SyncInterface $sync,
        LoggerInterface $logger
    )
    {
        $this->sync = $sync;
        $this->syncProcess = $syncProcess;
        $this->scopeConfig = $scopeConfig;
        $this->timeUpdater = $timeUpdater;
        $this->extendConfig = $extendConfig;
        $this->logger = $logger;
    }

    public function execute()
    {
        if ($this->extendConfig->isProductSyncByCronJobEnabled()) {

            $batchSize = $this->scopeConfig->getValue(self::BATCH_SIZE_PATH);
            if ($batchSize) {
                $this->batchSize = $batchSize;
                $this->sync->setBatchSize((int)$batchSize);
            }

            if ($this->totalBatches === null) {
                $this->totalBatches = $this->sync->getBatchesToProcess();
                $this->resetTotal = false;
            }

            $currentBatch = 1;
            $data = [];
            $hasErrors = false;

            do {
                try {
                    $productsBatch = $this->sync->getProducts($currentBatch);
                    $this->syncProcess->sync($productsBatch, $currentBatch);
                } catch (\Exception $e) {
                    $hasErrors = true;
                    $data['msg'] = 'Error found in products batch: ' . $currentBatch;
                }
                $currentBatch++;
            } while (!$hasErrors && $currentBatch <= $this->totalBatches);

            if (!$hasErrors && $currentBatch-1 == $this->totalBatches) {
                $this->resetTotal = true;
                $data['msg'] = $this->timeUpdater->updateLastSync();
                $data['status'] = "SUCCESS";
            } else {
                $this->resetTotal = true;
                $data['status'] = "ERROR";
            }

            if ($this->resetTotal) {
                unset($this->totalBatches);
            }

            $this->logger->info('Extend Cron for product sync executed with status: ' . $data['status'] . ', ' . $data['msg']);
        }
    }
}