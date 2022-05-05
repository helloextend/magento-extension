<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\ResourceModel\ContractCreate;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Extend\Warranty\Model\ContractCreate;
use Extend\Warranty\Model\ResourceModel\ContractCreate as ContractCreateResource;

/**
 * Class Collection
 *
 * ContractCreate Resource Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            ContractCreate::class,
            ContractCreateResource::class
        );
    }
}
