<?php

namespace Extend\Warranty\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * Upgrade DB schema
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();

        if (version_compare($context->getVersion(), '1.2.1', '<')) {
            $tableName = $setup->getTable('sales_order_item');
            $columnName = "contract_id";
            if ($connection->tableColumnExists($tableName, $columnName) === true) {
                $connection->modifyColumn(
                    $tableName,
                    $columnName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => '2M',
                        'nullable' => true,
                        'comment' => 'Extend Contract ID'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.2.2', '<')) {
            $tableName = $setup->getTable('sales_order_item');
            $columnName = "lead_token";
            if ($connection->tableColumnExists($tableName, $columnName) !== true) {
                $connection->addColumn(
                    $tableName,
                    $columnName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => '2M',
                        'nullable' => true,
                        'comment' => 'Extend Lead Token'
                    ]
                );
            }
        }

        $setup->endSetup();
    }
}