<?php
// phpcs:ignoreFile -- UpgradeSchema scripts are obsolete
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
use Psr\Log\LoggerInterface;

/**
 * Class UpgradeSchema
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UpgradeSchema constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

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
            try {
                $this->createExtendWarrantyContractCreateTable($setup);
            } catch (Zend_Db_Exception $exception) {
                $this->logger->critical($exception->getMessage());
            }
        }

        if (version_compare($context->getVersion(), '1.2.4', '<')) {
            try {
                $connection = $setup->getConnection();

                $tableWarrantyContact = $setup->getTable('extend_warranty_contract_create');

                if ($tableWarrantyContact) {

                    $salesInvoiceItemForeignKeyName = $connection->getForeignKeyName(
                        $tableWarrantyContact,
                        'invoice_item_id',
                        'sales_invoice_item',
                        'entity_id'
                    );

                    $uniqueIndexName = $connection->getIndexName(
                        $tableWarrantyContact,
                        ['order_item_id', 'invoice_item_id'],
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    );

                    $connection->changeColumn(
                        $tableWarrantyContact,
                        'qty_invoiced',
                        'qty',
                        [
                            'type' => Table::TYPE_DECIMAL,
                            'nullable' => false,
                            'unsigned' => false,
                            'comment' => 'Qty ordered'
                        ]
                    );

                    $connection->dropColumn($tableWarrantyContact, 'invoice_item_id');

                    $connection->addColumn(
                        $tableWarrantyContact,
                        'order_id',
                        [
                            'type' => Table::TYPE_INTEGER,
                            'identity' => false,
                            'nullable' => true,
                            'unsigned' => true,
                            'default' => '0',
                            'comment' => 'Order ID'
                        ]
                    );

                    if ($salesInvoiceItemForeignKeyName) {
                        $connection->dropForeignKey($tableWarrantyContact, $salesInvoiceItemForeignKeyName);
                    }

                    if ($uniqueIndexName) {
                        $connection->dropIndex($tableWarrantyContact, $uniqueIndexName);
                    }

                    $salesOrderItemForeignKeyName = $connection->getForeignKeyName(
                        $tableWarrantyContact,
                        'order_id',
                        $setup->getTable('sales_order'),
                        'entity_id'
                    );

                    $connection->addForeignKey(
                        $salesOrderItemForeignKeyName,
                        $tableWarrantyContact,
                        'order_id',
                        $setup->getTable('sales_order'),
                        'entity_id',
                        Table::ACTION_CASCADE
                    );

                    $uniqueIndexOrderName = $connection->getIndexName(
                        $tableWarrantyContact,
                        ['id', 'order_id'],
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    );

                    $connection->addIndex(
                        $tableWarrantyContact,
                        $uniqueIndexOrderName,
                        ['id', 'order_id'],
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    );

                }
            } catch (Zend_Db_Exception $exception) {
                $this->logger->critical($exception->getMessage());
            }
        }

        if (version_compare($context->getVersion(), '1.2.5', '<')) {
            try {
                $tableHistoricalOrders = $connection->newTable('extend_historical_orders');
                $tableHistoricalOrders->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => false, 'nullable' => false, 'unsigned' => true],
                    'Order Id'
                );
                $tableHistoricalOrders->addColumn(
                    'was_sent',
                    Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'unsigned' => true],
                    'Order sent status'
                );

                $salesOrderItemForeignKeyName = $connection->getForeignKeyName(
                    'extend_historical_orders',
                    'entity_id',
                    'sales_order',
                    'entity_id'
                );
                $tableHistoricalOrders->addForeignKey(
                    $salesOrderItemForeignKeyName,
                    'entity_id',
                    'sales_order',
                    'entity_id',
                    Table::ACTION_CASCADE
                );

                $connection->createTable($tableHistoricalOrders);
            } catch (Zend_Db_Exception $exception) {
                $this->logger->critical($exception->getMessage());
            }
        }

        if (version_compare($context->getVersion(), '1.2.6', '<')) {
            try {
                $connection = $setup->getConnection();
                $tableWarrantyContact = $setup->getTable('extend_warranty_contract_create');

                if ($tableWarrantyContact) {
                    $salesOrderItemForeignKeyName = $connection->getForeignKeyName(
                        'extend_historical_orders',
                        'entity_id',
                        'sales_order',
                        'entity_id'
                    );
                    if ($salesOrderItemForeignKeyName) {
                        $connection->dropForeignKey($tableWarrantyContact, $salesOrderItemForeignKeyName);
                    }
                }

            } catch (Zend_Db_Exception $exception) {
                $this->logger->critical($exception->getMessage());
            }
        }

        $setup->endSetup();
    }

    /**
     * Create `extend_warranty_contract_create` table
     *
     * @param SchemaSetupInterface $setup
     * @throws Zend_Db_Exception
     */
    protected function createExtendWarrantyContractCreateTable(SchemaSetupInterface $setup)
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
