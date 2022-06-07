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
 * Class CreateMode
 *
 * CreateMode Source Model
 */
class CreateMode implements OptionSourceInterface
{
    /**
     * Order creation mode values
     */
    public const EVENT_BASED = 0;
    public const SCHEDULED = 1;

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::EVENT_BASED, 'label' => __('Event Based')],
            ['value' => self::SCHEDULED, 'label' => __('Scheduled')],
        ];
    }
}
