<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Plugin;

use Magento\Catalog\Model\Product\Attribute\Backend\Price;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Extend\Warranty\Model\Product\Type;

/**
 * Class PriceBackend
 *
 * Warranty Price Validation Plugin
 */
class PriceBackend
{
    /**
     * Make price validation optional for warranty product
     *
     * @param Price $subject
     * @param callable $proceed
     * @param Product|DataObject $object
     * @return bool
     */
    public function aroundValidate(Price $subject, callable $proceed, $object)
    {
        if ($object instanceof Product && $object->getTypeId() === Type::TYPE_CODE) {
            return true;
        }

        return $proceed($object);
    }
}
