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

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\GetAfterDate;
use Extend\Warranty\Model\HistoricalOrdersSyncProcess;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * Class SendOrders
 */
class SendOrders
{
    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Send Historical Orders
     *
     * @var HistoricalOrdersSyncProcess
     */
    private $sendHistoricalOrders;

    /**
     * Get After Date
     *
     * @var GetAfterDate
     */
    private $getAfterDate;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SendOrders constructor
     *
     * @param DataHelper $dataHelper
     * @param HistoricalOrdersSyncProcess $sendHistoricalOrders
     * @param GetAfterDate $getAfterDate
     * @param LoggerInterface $logger
     */
    public function __construct(
        DataHelper                  $dataHelper,
        HistoricalOrdersSyncProcess $sendHistoricalOrders,
        GetAfterDate                $getAfterDate,
        LoggerInterface             $logger
    ) {
        $this->dataHelper = $dataHelper;
        $this->sendHistoricalOrders = $sendHistoricalOrders;
        $this->getAfterDate = $getAfterDate;
        $this->logger = $logger;
    }

    /**
     * Send Historical Orders Cron job
     */
    public function execute()
    {
        if ($this->dataHelper->isExtendEnabled(ScopeConfigInterface::SCOPE_TYPE_DEFAULT)) {
            $batchSize = 10;
            $sendAfterData = $this->getAfterDate->getAfterDate();

            $this->sendHistoricalOrders->execute($sendAfterData, $batchSize);
        }
    }
}
