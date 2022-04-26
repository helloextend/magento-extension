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

use Extend\Warranty\Model\SendHistoricalOrders;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Exception;

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
     * App State
     *
     * @var AppState
     */
    private $appState;

    /**
     * Send Historical Orders
     *
     * @var SendHistoricalOrders
     */
    private $sendHistoricalOrders;

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
     * @param SendHistoricalOrders $sendHistoricalOrders
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        AppState $appState,
        SendHistoricalOrders $sendHistoricalOrders,
        LoggerInterface $logger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->appState = $appState;
        $this->sendHistoricalOrders = $sendHistoricalOrders;
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
            )
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

        $this->sendHistoricalOrders->execute();

        $output->writeln("<comment>Process was finished.</comment>");
    }
}
