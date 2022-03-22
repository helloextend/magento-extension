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

namespace Extend\Warranty\Setup;

use Magento\Catalog\Model\Product;
use Extend\Warranty\Model\Product\Type;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Area;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Eav\Setup\EavSetupFactory;
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
     * @param LoggerInterface $logger
     */
    public function __construct(
        AppState $appState,
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $taxSetupFactory,
        LoggerInterface $logger
    ) {
        $this->appState = $appState;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $taxSetupFactory;
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
}
