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

namespace Extend\Warranty\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class AuthMode
 */
class AuthMode implements OptionSourceInterface
{
    /**
     * Auth mode values
     */
    const DEMO_VALUE = 0;
    const LIVE_VALUE = 1;

    /**
     * Auth modes
     */
    const DEMO = 'demo';
    const LIVE = 'live';

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::LIVE_VALUE, 'label' => __('Live')],
            ['value' => self::DEMO_VALUE, 'label' => __('Sandbox')],
        ];
    }
}
