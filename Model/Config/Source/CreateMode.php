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
 * Class CreateMode
 */
class CreateMode implements OptionSourceInterface
{
    /**
     * Order creation mode values
     */
    const ON_INVOICE = 0;
    const SCHEDULED = 1;

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::ON_INVOICE, 'label' => __('On Invoice')],
            ['value' => self::SCHEDULED, 'label' => __('Scheduled')],
        ];
    }
}
