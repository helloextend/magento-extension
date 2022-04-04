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

namespace Extend\Warranty\Model;

use Magento\Framework\Flag;

/**
 * Class ProductSyncFlag
 */
class ProductSyncFlag extends Flag
{
    /**
     * Flag Name
     */
    const FLAG_NAME = 'extend_product_sync';
}
