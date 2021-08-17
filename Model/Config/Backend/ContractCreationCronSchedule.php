<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Exception;

/**
 * Class ContractCreationCronSchedule
 */
class ContractCreationCronSchedule extends Value
{
    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        $frequency = $this->getData('groups/contracts/groups/cron/fields/frequency/value');
        if (!$this->isValid($frequency)) {
            throw new Exception(__('We can\'t save the cron expression.'));
        }

        return parent::beforeSave();
    }

    /**
     * Check if cron schedule expression is valid
     *
     * @param string $cronExpressionString
     * @return bool
     */
    protected function isValid(string $cronExpressionString): bool
    {
        $regExp = $this->buildRegExp();
        $cronExprArray = explode(' ', $cronExpressionString);
        foreach ($cronExprArray as $cronExp) {
            if (!preg_match($regExp, $cronExp)) {
                return false;
            }
        }

        return count($cronExprArray) === 5;
    }

    /**
     * Build a regular expression
     *
     * @return string
     */
    protected function buildRegExp(): string
    {
        return '/^(?:[1-9]?\d|\*)(?:(?:[\/-][1-9]?\d)|(?:,[1-9]?\d)+)?$/';
    }
}
