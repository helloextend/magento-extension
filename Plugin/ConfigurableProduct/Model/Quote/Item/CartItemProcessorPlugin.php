<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Plugin\ConfigurableProduct\Model\Quote\Item;

use Magento\ConfigurableProduct\Model\Quote\Item\CartItemProcessor;
use Magento\Quote\Api\Data\CartItemInterface;

/**
 * Class CartItemProcessorPlugin
 */
class CartItemProcessorPlugin
{
    /**
     * Prevent method call if custom option is null
     *
     * @param CartItemProcessor $subject
     * @param callable $proceed
     * @param CartItemInterface $cartItem
     * @return CartItemInterface
     */
    public function aroundProcessOptions(
        CartItemProcessor $subject,
        callable $proceed,
        CartItemInterface $cartItem
    ) {
        $product = $cartItem->getProduct();
        $attributesOption = $product->getCustomOption('attributes');

        if (!$attributesOption) {
            return $cartItem;
        } else {
            return $proceed($cartItem);
        }
    }
}
