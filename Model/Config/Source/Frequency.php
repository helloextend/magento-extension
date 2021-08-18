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
 * Class Frequency
 */
class Frequency implements OptionSourceInterface
{
    /**
     * Frequency shortcuts
     */
    const CRON_EVERY_MINUTE = 'MIN';
    const CRON_EVERY_HOUR = 'H';
    const CRON_DAILY = 'D';
    const CRON_WEEKLY = 'W';
    const CRON_MONTHLY = 'M';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        return [
            ['label' => __('Every Minute'), 'value' => self::CRON_EVERY_MINUTE],
            ['label' => __('Every Hour'), 'value' => self::CRON_EVERY_HOUR],
            ['label' => __('Daily'), 'value' => self::CRON_DAILY],
            ['label' => __('Weekly'), 'value' => self::CRON_WEEKLY],
            ['label' => __('Monthly'), 'value' => self::CRON_MONTHLY],
        ];
    }
}
