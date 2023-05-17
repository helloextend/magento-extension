<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class ContractCreate
 *
 * ContractCreate Resource Model
 */
class ExtendOrder extends AbstractDb
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('extend_order', 'order_id');
        $this->_isPkAutoIncrement = false;
    }
}
