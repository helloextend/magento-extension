<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

declare(strict_types=1);

namespace Extend\Warranty\Plugin\Checkout\CustomerData;

use Magento\Checkout\CustomerData\AbstractItem;
use Extend\Warranty\Model\Product\Type;

/**
 * Class AbstractItemPlugin
 */
class AbstractItemPlugin
{
    /**
     * Set 'isWarranty' flag for product image
     *
     * @param AbstractItem $subject
     * @param array $result
     * @return array
     */
    public function afterGetItemData(AbstractItem $subject, array $result): array
    {
        $result['product_image']['isWarranty'] = isset($result['product_type']) && $result['product_type'] === Type::TYPE_CODE;

        return $result;
    }
}
