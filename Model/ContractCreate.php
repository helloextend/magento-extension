<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model;

use Magento\Framework\Model\AbstractModel;
use Extend\Warranty\Model\ResourceModel\ContractCreate as ContractCreateResource;

/**
 * Class ContractCreate
 *
 * ContractCreate Model
 */
class ContractCreate extends AbstractModel
{
    /**
     * Success status
     */
    public const STATUS_SUCCESS = 'success';

    /**
     * Failed status
     */
    public const STATUS_FAILED = 'failed';

    /**
     * Partial status
     */
    public const STATUS_PARTIAL = 'partial';

    /**
     * Initialize invoice resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ContractCreateResource::class);
    }
}
