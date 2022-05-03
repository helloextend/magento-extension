<?php
// phpcs:ignoreFile -- InstallSchema scripts are obsolete

namespace Extend\Warranty\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_item'),
            'contract_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 50,
                'nullable' => true,
                'comment' => 'Extend Contract ID'
            ]
        );

        $setup->endSetup();
    }
}
