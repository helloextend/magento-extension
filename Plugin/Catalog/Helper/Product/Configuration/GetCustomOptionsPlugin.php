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

namespace Extend\Warranty\Plugin\Catalog\Helper\Product\Configuration;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product\Configuration;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Extend\Warranty\Model\Product\Type;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class GetCustomOptionsPlugin
 */
class GetCustomOptionsPlugin
{
    /**
     * Product Repository Interface
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * GetCustomOptionsPlugin constructor
     *
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

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
                $associatedProductSku = $associatedProductOption->getValue();
                $associatedProduct = $this->getProduct($associatedProductSku);
                if ($associatedProduct) {
                    $customOptions[] = [
                        'label' => __(Type::ASSOCIATED_PRODUCT_LABEL),
                        'value' => $associatedProduct->getName(),
                    ];
                }
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

    /**
     * Get product
     *
     * @param string $sku
     * @return ProductInterface|null
     */
    private function getProduct(string $sku): ?ProductInterface
    {
        try {
            $product = $this->productRepository->get($sku);
        } catch (LocalizedException $exception) {
            $product = null;
        }

        return $product;
    }
}
