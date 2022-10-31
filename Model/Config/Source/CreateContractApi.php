<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Create Contract
 *
 * Create Contract Source Model
 */
class CreateContractApi implements OptionSourceInterface
{
    /**
     * Contract creation values
     */
    public const UNSPECIFIED = 0;
    public const CONTACTS_API = 1;
    public const ORDERS_API = 2;

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::UNSPECIFIED, 'label' => __('No')],
            ['value' => self::ORDERS_API, 'label' => __('Orders API')],
        ];
    }
}
