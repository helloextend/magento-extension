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

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\ProductSyncFlag;
use Extend\Warranty\Model\ProductSyncProcess;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\FlagManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Extend\Warranty\Api\SyncInterface as ProductSyncModel;
use Symfony\Component\Console\Input\InputOption;
use Exception;

/**
 * Class SyncProducts
 *
 * Sync Product Console Command
 */
class SyncProducts extends Command
{
    /**
     * Batch size input key
     */
    public const INPUT_KEY_BATCH_SIZE = 'batch_size';

    /**
     * State
     *
     * @var AppState
     */
    private $appState;

    /**
     * Warranty Api Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Flag Manager Model
     *
     * @var FlagManager
     */
    private $flagManager;

    /**
     * Product Sync Model
     *
     * @var ProductSyncProcess
     */
    private $productSyncProcess;

    /**
     * Logger Model
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SyncProducts constructor
     *
     * @param AppState $appState
     * @param DataHelper $dataHelper
     * @param FlagManager $flagManager
     * @param ProductSyncProcess $productSyncProcess
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        AppState $appState,
        DataHelper $dataHelper,
        FlagManager $flagManager,
        ProductSyncProcess $productSyncProcess,
        LoggerInterface $logger,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->appState = $appState;
        $this->dataHelper = $dataHelper;
        $this->flagManager = $flagManager;
        $this->productSyncProcess = $productSyncProcess;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::INPUT_KEY_BATCH_SIZE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Set product batch size'
            )
        ];

        $this->setName('extend:sync:products');
        $this->setDescription('Sync products from Magento 2 to Extend');
        $this->setDefinition($options);

        parent::configure();
    }

    /**
     * @inheritdoc
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
        return 0;
    }

    /**
     * Sync products
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->dataHelper->isExtendEnabled(ScopeConfigInterface::SCOPE_TYPE_DEFAULT)) {
            $output->writeln("<error>Extension is disabled. Please, check the configuration settings.</error>");

            return;
        }

        if ((bool)$this->flagManager->getFlagData(ProductSyncFlag::FLAG_NAME)) {
            $output->writeln("<error>Product sync has already started by another process.</error>");

            return;
        }

        $defaultBatchSize = abs((int)$input->getOption(self::INPUT_KEY_BATCH_SIZE));
        if (!$defaultBatchSize) {
            $defaultBatchSize = null;
        } elseif ($defaultBatchSize > ProductSyncModel::DEFAULT_BATCH_SIZE) {
            $output->writeln("<error>Invalid batch size, value must be between 1-100.</error>");

            return;
        }

        $this->flagManager->saveFlag(ProductSyncFlag::FLAG_NAME, true);
        $output->writeln("<comment>Process was started.</comment>");

        $this->productSyncProcess->execute($defaultBatchSize);

        $this->flagManager->deleteFlag(ProductSyncFlag::FLAG_NAME);
        $output->writeln("<comment>Process was finished. See sync log for details.</comment>");
    }
}
