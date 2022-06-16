<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Event
 *
 * Event Source Model
 */
class Event implements OptionSourceInterface
{
    /**
     * Order creation event values
     */
    public const ORDER_CREATE = 0;
    public const INVOICE_CREATE = 1;
    public const SHIPMENT_CREATE = 2;

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::ORDER_CREATE, 'label' => __('Order Create')],
            ['value' => self::INVOICE_CREATE, 'label' => __('Invoice Create')],
            ['value' => self::SHIPMENT_CREATE, 'label' => __('Shipment Create')],
        ];
    }
}
