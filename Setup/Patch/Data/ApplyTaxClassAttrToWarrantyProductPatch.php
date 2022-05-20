<?php

namespace Extend\Warranty\Setup\Patch\Data;

use Extend\Warranty\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\NonTransactionableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * class AddWarrantyProductPatch
 *
 * Add tax class to warranty product
 */
class ApplyTaxClassAttrToWarrantyProductPatch implements DataPatchInterface, PatchRevertableInterface, NonTransactionableInterface
{
    /**
     * Attribute code
     */
    protected const TAX_CLASS_ID_ATTR_CODE = 'tax_class_id';

    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $taxSetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $taxSetupFactory,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $taxSetupFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

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

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        try {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $taxClassIdApplyTo = $eavSetup->getAttribute(
                Product::ENTITY,
                self::TAX_CLASS_ID_ATTR_CODE,
                'apply_to'
            );

            if ($taxClassIdApplyTo) {
                $productTypes = explode(',', $taxClassIdApplyTo);
                if (in_array(Type::TYPE_CODE, $productTypes)) {
                    $productTypes = array_diff($productTypes, [Type::TYPE_CODE]);
                    $noWarrantyProductTypes = implode(',', $productTypes);

                    $eavSetup->updateAttribute(
                        Product::ENTITY,
                        self::TAX_CLASS_ID_ATTR_CODE,
                        ['apply_to' => $noWarrantyProductTypes]
                    );
                }
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            \Extend\Warranty\Setup\Patch\Data\AddWarrantyProductPatch::class
        ];
    }
}
