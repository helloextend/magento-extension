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
use Extend\Warranty\Model\ProductSyncFlag;
use Extend\Warranty\Model\ProductSyncProcess;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\FlagManager;
use Psr\Log\LoggerInterface;

/**
 * Class SyncProducts
 */
class SyncProducts
{
    /**
     * Product Sync Process
     *
     * @var ProductSyncProcess
     */
    private $productSyncProcess;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

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
     * SyncProducts constructor
     *
     * @param DataHelper $dataHelper
     * @param ProductSyncProcess $productSyncProcess
     * @param FlagManager $flagManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        DataHelper $dataHelper,
        ProductSyncProcess $productSyncProcess,
        FlagManager $flagManager,
        LoggerInterface $logger
    ) {
        $this->dataHelper = $dataHelper;
        $this->productSyncProcess = $productSyncProcess;
        $this->flagManager = $flagManager;
        $this->logger = $logger;
    }

    /**
     * Sync products by cron
     */
    public function execute(): void
    {
        if (
            !$this->dataHelper->isExtendEnabled(ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
            || !$this->dataHelper->isProductSyncByCronEnabled()
        ) {
            return;
        }

        if ((bool)$this->flagManager->getFlagData(ProductSyncFlag::FLAG_NAME)) {
            $this->logger->error('Product sync has already started by another process.');

            return;
        }

        $this->flagManager->saveFlag(ProductSyncFlag::FLAG_NAME, true);
        $this->productSyncProcess->execute();
        $this->flagManager->deleteFlag(ProductSyncFlag::FLAG_NAME);
    }
}
