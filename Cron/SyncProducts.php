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
use Extend\Warranty\Model\ProductSyncProcess;

/**
 * Class SyncProducts
 */
class SyncProducts
{
    /**
     * ProductSyncProcess
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
     * SyncProducts constructor
     *
     * @param DataHelper $dataHelper
     * @param ProductSyncProcess $productSyncProcess
     */
    public function __construct(
        DataHelper $dataHelper,
        ProductSyncProcess $productSyncProcess
    ) {
        $this->productSyncProcess = $productSyncProcess;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Sync products by cron
     */
    public function execute(): void
    {
        if (!$this->dataHelper->isExtendEnabled() || !$this->dataHelper->isProductSyncByCronEnabled()) {
            return;
        }

        $this->productSyncProcess->execute();
    }
}
