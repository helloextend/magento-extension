<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Console\Command;

use Extend\Warranty\Model\ProductSyncFlag;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Magento\Framework\FlagManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Class ResetProductSyncFlag
 */
class ResetProductSyncFlag extends Command
{
    /**
     * App State
     *
     * @var AppState
     */
    private $appState;

    /**
     * Flag Manager
     *
     * @var FlagManager
     */
    private $flagManager;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ResetProductSyncFlag constructor
     *
     * @param AppState $appState
     * @param FlagManager $flagManager
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        AppState $appState,
        FlagManager $flagManager,
        LoggerInterface $logger,
        string $name = null
    ) {
        $this->appState = $appState;
        $this->flagManager = $flagManager;
        $this->logger = $logger;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('extend:sync-products:reset-flag');
        $this->setDescription('Reset product sync flag to unlock sync process');

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
     * Reset product sync flag
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->flagManager->deleteFlag(ProductSyncFlag::FLAG_NAME);
        $output->writeln("<comment>Product sync flag has been reset.</comment>");
    }
}
