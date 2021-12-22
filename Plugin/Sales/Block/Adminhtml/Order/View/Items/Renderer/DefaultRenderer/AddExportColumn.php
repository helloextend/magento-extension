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

namespace Extend\Warranty\Plugin\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer;

use Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer;
use Extend\Warranty\Plugin\Sales\Block\Adminhtml\Order\View\Items\AbstractUpdateColumnsOrderPlugin;
use Magento\Framework\Module\Manager as ModuleManager;

/**
 * Class AddExportColumn
 */
class AddExportColumn
{
    /**
     * Module Manager
     *
     * @var ModuleManager
     */
    private $moduleManager;
    
    /**
     * Columns order
     */
    const COLUMNS_ORDER = [
        'product',
        'status',
        'export',
        'price-original',
        'price',
        'qty',
        'subtotal',
        'tax-amount',
        'tax-percent',
        'member_discount',
        'discont',
        'total',
        'refund',
    ];

    /**
     * AddExportColumn constructor
     *
     * @param ModuleManager $moduleManager
     */
    public function __construct(
        ModuleManager $moduleManager
    ) {
        $this->moduleManager = $moduleManager;
    }

    /**
     * Add export column
     *
     * @param DefaultRenderer $subject
     * @param array $result
     * @return array
     */
    public function afterGetColumns(DefaultRenderer $subject, array $result): array
    {
        if (!empty($result) && $this->moduleManager->isEnabled('Wyomind_OrdersExportTool')) {
            $result['export'] = 'col-export';
            $result = $this->getColumns(self::COLUMNS_ORDER, $result);
        }

        return $result;
    }

    /**
     * Get columns
     *
     * @param array $columnsOrder
     * @param array $columnsResult
     * @return array
     */
    private function getColumns(array $columnsOrder, array $columnsResult): array
    {
        $columns = [];
        foreach ($columnsOrder as $columnName) {
            if (isset($columnsResult[$columnName])) {
                $columns[$columnName] = $columnsResult[$columnName];
            }
        }

        return $columns;
    }
}
