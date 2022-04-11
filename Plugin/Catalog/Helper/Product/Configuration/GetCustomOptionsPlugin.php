<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Plugin\Catalog\Helper\Product\Configuration;

use Magento\Catalog\Helper\Product\Configuration;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Extend\Warranty\Model\Product\Type;

/**
 * Class GetCustomOptionsPlugin
 */
class GetCustomOptionsPlugin
{
    /**
     * Set custom options for warranty
     *
     * @param Configuration $subject
     * @param array $result
     * @param ItemInterface $item
     * @return array
     */
    public function afterGetCustomOptions(Configuration $subject, array $result, ItemInterface $item): array
    {
        $product = $item->getProduct();
        if ($product && $product->getTypeId() === Type::TYPE_CODE) {
            $customOptions = [];

            $associatedProductOption = $product->getCustomOption(Type::ASSOCIATED_PRODUCT);
            if ($associatedProductOption && $associatedProductOption->getValue()) {
                $customOptions[] = [
                    'label' => __(Type::ASSOCIATED_PRODUCT_LABEL),
                    'value' => $associatedProductOption->getValue(),
                ];
            }

            $warrantyTermOption = $product->getCustomOption(Type::TERM);
            if ($warrantyTermOption && $warrantyTermOption->getValue()) {
                $warrantyTerm = (int)$warrantyTermOption->getValue() / 12;
                $optionValue = ($warrantyTerm > 1) ? $warrantyTerm . ' years' : $warrantyTerm . ' year';

                $customOptions[] = [
                    'label' => __(Type::TERM_LABEL),
                    'value' => $optionValue,
                ];
            }

            $result = array_merge($result, $customOptions);
        }

        return $result;
    }
}
