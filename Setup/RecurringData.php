<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

declare(strict_types=1);

namespace Extend\Warranty\Setup;

use Extend\Warranty\Model\ProductSyncFlag;
use Magento\Framework\FlagManager;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class RecurringData
 */
class RecurringData implements InstallDataInterface
{
    /**
     * Flag Manager
     *
     * @var FlagManager
     */
    private $flagManager;

    /**
     * RecurringData constructor
     *
     * @param FlagManager $flagManager
     */
    public function __construct(
        FlagManager $flagManager
    ) {
        $this->flagManager = $flagManager;
    }

    /**
     * Reset product sync flag
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $flagData = $this->flagManager->getFlagData(ProductSyncFlag::FLAG_NAME);
        if ($flagData && is_string($flagData)) {
            $this->flagManager->deleteFlag(ProductSyncFlag::FLAG_NAME);
        }
    }
}
