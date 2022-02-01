<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

declare(strict_types=1);

namespace Extend\Warranty\Cron;

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\ContractCreateProcess;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class CreateContracts
 */
class CreateContracts
{
    /**
     * Contract Create Process
     *
     * @var ContractCreateProcess
     */
    private $contractCreateProcess;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * CreateContracts constructor
     *
     * @param ContractCreateProcess $contractCreateProcess
     * @param DataHelper $dataHelper
     */
    public function __construct(
        ContractCreateProcess $contractCreateProcess,
        DataHelper $dataHelper
    ) {
        $this->contractCreateProcess = $contractCreateProcess;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Create warranty contracts
     *
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        if (!$this->dataHelper->isExtendEnabled() || $this->dataHelper->isWarrantyContractEnabled()) {
            return;
        }

        $this->contractCreateProcess->execute();
    }
}
