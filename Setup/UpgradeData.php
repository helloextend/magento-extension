<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Setup;

use Magento\Catalog\Model\Product;
use Extend\Warranty\Model\Product\Type;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Area;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Extend\Warranty\Model\GetAfterDate;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Class UpgradeData
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * Attribute code
     */
    const TAX_CLASS_ID_ATTR_CODE = 'tax_class_id';

    /**
     * App State
     *
     * @var AppState
     */
    private $appState;

    /**
     * Eav Setup Factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Module Data Setup Interface
     *
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * Config Writer
     *
     * @var WriterInterface
     */
    private $configWriter;

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
     * UpgradeData constructor
     *
     * @param AppState $appState
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $taxSetupFactory
     * @param WriterInterface $configWriter
     * @param GetAfterDate $getAfterDate
     * @param LoggerInterface $logger
     */
    public function __construct(
        AppState $appState,
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $taxSetupFactory,
        WriterInterface $configWriter,
        GetAfterDate $getAfterDate,
        LoggerInterface $logger
    ) {
        $this->appState = $appState;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $taxSetupFactory;
        $this->configWriter = $configWriter;
        $this->getAfterDate = $getAfterDate;
        $this->logger = $logger;
    }

    /**
     * Upgrade data
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws Exception
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.2.4', '<')) {
            $this->appState->emulateAreaCode(
                Area::AREA_ADMINHTML,
                [$this, 'applyTaxClassAttrToWarrantyProduct']
            );
        }

        if (version_compare($context->getVersion(), '1.2.6', '<')) {
            $this->appState->emulateAreaCode(
                Area::AREA_ADMINHTML,
                [$this, 'setAfterDateSendOrders']
            );
        }
    }

    /**
     * Apply tax class attribute to warranty product type
     */
    public function applyTaxClassAttrToWarrantyProduct()
    {
        try {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $taxClassIdApplyTo = $eavSetup->getAttribute(
                Product::ENTITY,
                self::TAX_CLASS_ID_ATTR_CODE,
                'apply_to'
            );

            if ($taxClassIdApplyTo) {
                $productTypes = explode(',', $taxClassIdApplyTo);
                if (!in_array(Type::TYPE_CODE, $productTypes)) {
                    $productTypes[] = Type::TYPE_CODE;
                    $updatedTaxClassIdApplyTo = implode(',', $productTypes);

                    $eavSetup->updateAttribute(
                        Product::ENTITY,
                        self::TAX_CLASS_ID_ATTR_CODE,
                        ['apply_to' => $updatedTaxClassIdApplyTo]
                    );
                }
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * Apply after date value for send history orders
     */
    public function setAfterDateSendOrders()
    {
        $afterDate = $this->getAfterDate->getAfterDateTwoYears();
        $this->configWriter->save(GetAfterDate::XML_PATH_AFTER_DATE, $afterDate);
    }
}
