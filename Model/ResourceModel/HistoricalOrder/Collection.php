<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\ResourceModel\HistoricalOrder;


use Extend\Warranty\Api\Data\HistoricalOrderInterface;
use Extend\Warranty\Model\HistoricalOrder;
use Extend\Warranty\Model\ResourceModel\HistoricalOrder as HistoricalOrderResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /**
     * Identifier field name for collection items
     *
     * @var string
     */
    protected $_idFieldName = HistoricalOrderInterface::ENTITY_ID;

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(HistoricalOrder::class, HistoricalOrderResource::class);
    }
}
