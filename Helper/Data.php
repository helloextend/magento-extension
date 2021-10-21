<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Helper;

use Extend\Warranty\Model\Product\Type;

/**
 * Class Data
 */
class Data
{
    /**
     * `Contract ID` field
     */
    const CONTRACT_ID = 'contract_id';

    /**
     * Cron regular expressions
     */
    const CRON_REG_EXP = '/^(?:[1-9]?\d|\*)(?:(?:[\/-][1-9]?\d)|(?:,[1-9]?\d)+)?$/';

    /**
     * List of not allowed product types
     */
    const NOT_ALLOWED_TYPES = [
        Type::TYPE_CODE,
    ];

    /**
     * Format price
     *
     * @param $price
     * @return float
     */
    public function formatPrice($price): float
    {
        if (empty($price)) {
            return 0;
        }

        $floatPrice = (float) $price;

        $formattedPrice = number_format(
            $floatPrice,
            2,
            '',
            ''
        );

        return (float) $formattedPrice;
    }

    /**
     * Remove format price
     *
     * @param int $price
     * @return float
     */
    public function removeFormatPrice(int $price): float
    {
        $price = (string)$price;

        $price = substr_replace(
            $price,
            '.',
            strlen($price) - 2,
            0
        );

        return (float) $price;
    }

    /**
     * Check if cron schedule expression is valid
     *
     * @param string $cronExpressionString
     * @return bool
     */
    public function isCronExpressionValid(string $cronExpressionString): bool
    {
        $cronExprArray = explode(' ', $cronExpressionString);
        foreach ($cronExprArray as $cronExp) {
            if (!preg_match(self::CRON_REG_EXP, $cronExp)) {
                $isValid = false;
                break;
            }
        }

        return $isValid ?? count($cronExprArray) === 5;
    }
}
