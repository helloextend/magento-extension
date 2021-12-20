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

namespace Extend\Warranty\Ui\DataProvider\Product\Form\Modifier;

use Extend\Warranty\Model\Product\Type;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;

/**
 * Class WarrantyPrice
 */
class WarrantyPrice extends AbstractModifier
{
    /**
     * Price container
     */
    const PRICE_CONTAINER = 'container_price';

    /**
     * Advanced pricing button
     */
    const ADVANCED_PRICING_BUTTON = 'advanced_pricing_button';

    /**
     * Locator Interface
     *
     * @var LocatorInterface
     */
    private $locator;

    /**
     * Array Manager
     *
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * WarrantyPrice constructor
     *
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data): array
    {
        return $data;
    }

    /**
     * Disable price fields for warranty product
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta): array
    {
        $product = $this->locator->getProduct();
        if ($product && $product->getTypeId() === Type::TYPE_CODE) {
            $groupCode = $this->getGroupCodeByField($meta, ProductAttributeInterface::CODE_PRICE)
                ?: $this->getGroupCodeByField($meta, self::PRICE_CONTAINER);

            if ($groupCode) {
                $priceConfigPath = $groupCode . '/children/' . self::PRICE_CONTAINER . '/children/'
                    . ProductAttributeInterface::CODE_PRICE . '/arguments/data/config';

                if ($this->arrayManager->exists($priceConfigPath, $meta)) {
                    $this->arrayManager->merge(
                        $priceConfigPath,
                        $meta,
                        ['disabled' => true]
                    );
                }

                $advancedPricingButtonConfigPath = $groupCode . '/children/' . self::PRICE_CONTAINER . '/children/'
                    . self::ADVANCED_PRICING_BUTTON . '/arguments/data/config';

                if ($this->arrayManager->exists($advancedPricingButtonConfigPath, $meta)) {
                    $this->arrayManager->merge(
                        $advancedPricingButtonConfigPath,
                        $meta,
                        ['disabled' => true, 'visible' => false]
                    );
                }
            }
        }

        return $meta;
    }
}
