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
use Extend\Warranty\Model\ContractCreateProcess;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State as AppState;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

/**
 * Class CreateContracts
 *
 * Create Contracts Console Command
 */
class CreateContracts extends Command
{
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
     * Contract Creation Process Model
     *
     * @var ContractCreateProcess
     */
    private $contractCreateProcess;

    /**
     * Logger Model
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CreateContracts constructor
     *
     * @param AppState $appState
     * @param DataHelper $dataHelper
     * @param ContractCreateProcess $contractCreateProcess
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        AppState $appState,
        DataHelper $dataHelper,
        ContractCreateProcess $contractCreateProcess,
        LoggerInterface $logger,
        ?string $name = null
    ) {
        $this->appState = $appState;
        $this->dataHelper = $dataHelper;
        $this->contractCreateProcess = $contractCreateProcess;
        $this->logger = $logger;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('extend:contracts:create');
        $this->setDescription('Create warranty contracts');
        parent::configure();
    }

    /**
     * @inheritDoc
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
            $output->writeln("Something went wrong while creating the warranty contracts.");
            $this->logger->error($exception->getMessage());
        }
        return 0;
    }

    /**
     * Create warranty contracts
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

        $output->writeln("<comment>Process was started.</comment>");
        $this->contractCreateProcess->execute();
        $output->writeln("<comment>Process was finished.</comment>");
    }
}
