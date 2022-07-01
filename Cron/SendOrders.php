<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\FlagManager;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\HistoricalOrdersSyncFlag;
use Extend\Warranty\Model\HistoricalOrdersSyncProcess;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * Class SendOrders
 */
class SendOrders
{

    /**
     * Flag Manager
     *
     * @var FlagManager
     */
    private $flagManager;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Historical Orders Sync Process
     *
     * @var HistoricalOrdersSyncProcess
     */
    private $sendHistoricalOrders;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param FlagManager $flagManager
     * @param DataHelper $dataHelper
     * @param HistoricalOrdersSyncProcess $sendHistoricalOrders
     * @param LoggerInterface $logger
     */
    public function __construct(
        FlagManager                 $flagManager,
        DataHelper                  $dataHelper,
        HistoricalOrdersSyncProcess $sendHistoricalOrders,
        LoggerInterface             $logger
    ) {
        $this->flagManager = $flagManager;
        $this->dataHelper = $dataHelper;
        $this->sendHistoricalOrders = $sendHistoricalOrders;
        $this->logger = $logger;
    }

    /**
     * Send Historical Orders Cron job
     */
    public function execute()
    {
        if (!$this->dataHelper->isExtendEnabled(ScopeConfigInterface::SCOPE_TYPE_DEFAULT)) {
            return;
        }

        if ((bool)$this->flagManager->getFlagData(HistoricalOrdersSyncFlag::FLAG_NAME)) {
            $this->logger->error('Historical orders sync has already started by another process.');
            return;
        }

        $this->flagManager->saveFlag(HistoricalOrdersSyncFlag::FLAG_NAME, true);
        try {
            $this->sendHistoricalOrders->execute();
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
            $this->flagManager->deleteFlag(HistoricalOrdersSyncFlag::FLAG_NAME);
        }
        $this->flagManager->deleteFlag(HistoricalOrdersSyncFlag::FLAG_NAME);
    }
}
