<?php
/**
 * Created by PhpStorm.
 * User: lazaro
 * Date: 13/05/19
 * Time: 05:33 PM
 */

namespace Extend\Warranty\Model\Product;

use Magento\Catalog\Model\Product\Type\Price as AbstractPrice;

class Price extends AbstractPrice
{
    /**
     * @inheritDoc
     */
    public function getFinalPrice($qty, $product) //phpcs:ignore
    {
        return parent::getFinalPrice($qty, $product); // TODO: Change the autogenerated stub
    }
}
