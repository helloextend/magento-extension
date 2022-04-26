<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Console\Command;

use Exception;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\SendHistoricalOrders;
use Extend\Warranty\Model\GetAfterDate;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SendOrders
 */
class SendOrders extends Command
{
    /**
     * Batch size input key
     */
    const INPUT_KEY_BATCH_SIZE = 'batch_size';

    /**
     * Batch size default value
     */
    const BATCH_SIZE = 10;

    /**
     * Send orders after (datetime) input key
     */
    const INPUT_KEY_SEND_AFTER = 'send_after';

    /**
     * App State
     *
     * @var AppState
     */
    private $appState;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Date conversion model
     *
     * @var DateTime
     */
    private $dateTime;

    /**
     * Send Historical Orders
     *
     * @var SendHistoricalOrders
     */
    private $sendHistoricalOrders;

    /**
     * Get After Date
     *
     * @var GetAfterDate
     */
    private $getAfterDate;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SyncProducts constructor
     *
     * @param AppState $appState
     * @param DataHelper $dataHelper
     * @param DateTime $dateTime
     * @param SendHistoricalOrders $sendHistoricalOrders
     * @param GetAfterDate $getAfterDate
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        AppState $appState,
        DataHelper $dataHelper,
        DateTime $dateTime,
        SendHistoricalOrders $sendHistoricalOrders,
        GetAfterDate $getAfterDate,
        LoggerInterface $logger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->appState = $appState;
        $this->dataHelper = $dataHelper;
        $this->dateTime = $dateTime;
        $this->sendHistoricalOrders = $sendHistoricalOrders;
        $this->getAfterDate = $getAfterDate;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::INPUT_KEY_BATCH_SIZE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Set orders batch size'
            ),
            new InputOption(
                self::INPUT_KEY_SEND_AFTER,
                null,
                InputOption::VALUE_OPTIONAL,
                'Send orders after (\'Y-m-d\') input key'
            ),
        ];

        $this->setName('extend:send:orders');
        $this->setDescription('Send historical orders from Magento 2 to Extend');
        $this->setDefinition($options);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->emulateAreaCode(
                Area::AREA_ADMINHTML,
                [$this, 'doExecute'],
                [$input, $output]
            );
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
        }
    }

    /**
     * Send Orders
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<comment>Process was started.</comment>");

        if ($this->dataHelper->isExtendEnabled(ScopeConfigInterface::SCOPE_TYPE_DEFAULT)) {
            $defaultBatchSize = abs((int)$input->getOption(self::INPUT_KEY_BATCH_SIZE));
            $defaultSendAfter = $input->getOption(self::INPUT_KEY_SEND_AFTER);

            $batchSize = !empty($defaultBatchSize) ? $defaultBatchSize : self::BATCH_SIZE;
            $sendAfterData = $this->dateTime->gmtDate('Y-m-d', $defaultSendAfter);

            if (empty($sendAfterData)) {
                $sendAfterData = $this->getAfterDate->getAfterDate();
            }
            $paramsComment = sprintf('Batch size: %d; Send orders after: %s', $batchSize, $sendAfterData);
            $output->writeln("<comment>$paramsComment</comment>");

            $this->sendHistoricalOrders->execute($sendAfterData, $batchSize);

            $output->writeln("<comment>Process was finished.</comment>");
        } else {
            $output->writeln("<error>Extension is disabled. Please, check the configuration settings.</error>");
        }
    }
}
