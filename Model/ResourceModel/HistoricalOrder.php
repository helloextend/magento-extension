<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Extend\Warranty\Api\Data\HistoricalOrderInterface;

/**
 * Class HistoricalOrder
 */
class HistoricalOrder extends AbstractDb
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(HistoricalOrderInterface::DB_TABLE_NAME, 'entity_id');
        $this->_isPkAutoIncrement = false;
    }
}
