<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model;

use Extend\Warranty\Api\Data\HistoricalOrderInterfaceFactory;
use Extend\Warranty\Model\ResourceModel\HistoricalOrder as HistoricalOrderResource;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Class SendHistoricalOrders
 */
class HistoricalOrdersSyncProcess
{
    /**
     * Historical Order Factory
     *
     * @var HistoricalOrderInterfaceFactory
     */
    private $historicalOrderFactory;

    /**
     * Historical Order Resource
     *
     * @var HistoricalOrderResource
     */
    private $historicalOrderResource;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SendHistoricalOrders constructor
     *
     * @param HistoricalOrderInterfaceFactory $historicalOrderFactory
     * @param HistoricalOrderResource $historicalOrderResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        HistoricalOrderInterfaceFactory $historicalOrderFactory,
        HistoricalOrderResource $historicalOrderResource,
        LoggerInterface $logger
    ) {
        $this->historicalOrderFactory = $historicalOrderFactory;
        $this->historicalOrderResource = $historicalOrderResource;
        $this->logger = $logger;
    }

    /**
     * Send Historical Orders
     *
     * @param string $sendAfterData
     * @param int $batchSize
     *
     * @return $this
     */
    public function execute(string $sendAfterData, int $batchSize)
    {
        //todo -> send historical orders
        /** @var HistoricalOrder $historicalOrder */
        $historicalOrder = $this->historicalOrderFactory->create();
        $historicalOrder->setEntityId(33);
        $historicalOrder->setWasSent(1);

        try {
            $this->historicalOrderResource->save($historicalOrder);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $this;
    }
}
