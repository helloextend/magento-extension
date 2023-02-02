<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ProductPagePlacement
 *
 * ProductPagePlacement Source Model
 */
class ProductPagePlacement implements OptionSourceInterface
{
    /**
     * PDP Offers Button placement values
     */
    public const ACTIONS_BEFORE = 0;
    public const ACTIONS_AFTER = 1;
    public const ADD_TO_CART_BEFORE = 2;
    public const ADD_TO_CART_AFTER = 3;
    public const QUANTITY_BEFORE = 4;
    public const QUANTITY_AFTER = 5;
    public const OPTIONS_BEFORE = 6;
    public const OPTIONS_AFTER = 7;
    public const SOCIAL_BEFORE = 8;
    public const SOCIAL_AFTER = 9;

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::ACTIONS_BEFORE, 'label' => __('Before Actions block')],
            ['value' => self::ACTIONS_AFTER, 'label' => __('After Actions block')],
            ['value' => self::ADD_TO_CART_BEFORE, 'label' => __('Before "Add to cart" button')],
            ['value' => self::ADD_TO_CART_AFTER, 'label' => __('After "Add to cart" button')],
            ['value' => self::QUANTITY_BEFORE, 'label' => __('Before "Qty" input')],
            ['value' => self::QUANTITY_AFTER, 'label' => __('After "Qty" input')],
            ['value' => self::OPTIONS_BEFORE, 'label' => __('Before Options fieldset')],
            ['value' => self::OPTIONS_AFTER, 'label' => __('After Options fieldset')],
            ['value' => self::SOCIAL_BEFORE, 'label' => __('Before Social links')],
            ['value' => self::SOCIAL_AFTER, 'label' => __('After Social links')],
        ];
    }
}
