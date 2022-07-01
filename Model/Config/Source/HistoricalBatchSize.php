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
 * Class HistoricalBatchSize
 *
 * BatchSize Source Model
 */
class HistoricalBatchSize implements OptionSourceInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 1, 'label' => __('1')],
            ['value' => 5, 'label' => __('5')],
            ['value' => 10, 'label' => __('10')],
        ];
    }
}
