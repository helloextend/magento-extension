<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Zend_Db_Exception;

/**
 * Class UpgradeSchema
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrade DB schema
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @throws Zend_Db_Exception
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
                        'type' => Table::TYPE_TEXT,
                        'length' => '2M',
                        'nullable' => true,
                        'comment' => 'Extend Contract ID',
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
                        'type' => Table::TYPE_TEXT,
                        'length' => '2M',
                        'nullable' => true,
                        'comment' => 'Extend Lead Token',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.2.3', '<')) {
            $this->createExtendWarrantyContractCreateTable($setup);
        }

        $setup->endSetup();
    }

    /**
     * Create `extend_warranty_contract_create` table
     *
     * @param SchemaSetupInterface $setup
     * @throws Zend_Db_Exception
     */
    protected function createExtendWarrantyContractCreateTable(SchemaSetupInterface $setup): void
    {
        $connection = $setup->getConnection();

        $table = $connection->newTable('extend_warranty_contract_create');
        $table->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'unsigned' => true, 'primary' => true],
            'ID'
        );
        $table->addColumn(
            'order_item_id',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'unsigned' => true],
            'Order Item ID'
        );
        $table->addColumn(
            'invoice_item_id',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'unsigned' => true],
            'Invoice Item ID'
        );
        $table->addColumn(
            'qty_invoiced',
            Table::TYPE_DECIMAL,
            [12,4],
            ['nullable' => false, 'unsigned' => false],
            'Qty Invoiced'
        );
        $table->addColumn(
            'status',
            Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Status'
        );
        $table->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
            'Created At'
        );
        $table->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
            'Updated At'
        );

        $salesOrderItemForeignKeyName = $connection->getForeignKeyName(
            'extend_warranty_contract_create',
            'order_item_id',
            'sales_order_item',
            'item_id'
        );
        $table->addForeignKey(
            $salesOrderItemForeignKeyName,
            'order_item_id',
            'sales_order_item',
            'item_id',
            Table::ACTION_CASCADE
        );

        $salesInvoiceItemForeignKeyName = $connection->getForeignKeyName(
            'extend_warranty_contract_create',
            'invoice_item_id',
            'sales_invoice_item',
            'entity_id'
        );
        $table->addForeignKey(
            $salesInvoiceItemForeignKeyName,
            'invoice_item_id',
            'sales_invoice_item',
            'entity_id',
            Table::ACTION_CASCADE
        );

        $uniqueIndexName = $connection->getIndexName(
            'extend_warranty_contract_create',
            ['order_item_id', 'invoice_item_id'],
            AdapterInterface::INDEX_TYPE_UNIQUE
        );
        $table->addIndex(
            $uniqueIndexName,
            ['order_item_id', 'invoice_item_id'],
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        );

        $connection->createTable($table);
    }
}
