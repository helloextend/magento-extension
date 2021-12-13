<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Plugin\Raveinfosys\General\Model\Carrier;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Raveinfosys\General\Model\Carrier;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Quote\Model\Quote\Address\RateRequest;

/**
 * Class GetItemsPlugin
 */
class GetItemsPlugin
{
    /**
     * Item weight
     */
    const ITEM_WEIGHT = 70;

    /**
     * Item types
     */
    const PARCEL_ITEM_TYPE = 'parcel';
    const HANDLING_UNIT_ITEM_TYPE = 'handling_unit';

    /**
     * Box handling unit type
     */
    const BOX_HANDLING_UNIT_TYPE = 'box';

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * GetItemsPlugin constructor
     *
     * @param DataHelper $dataHelper
     */
    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * Exclude virtual products
     *
     * @param Carrier $subject
     * @param callable $proceed
     * @param RateRequest $request
     * @return array
     */
    public function aroundGetItems(Carrier $subject, callable $proceed, RateRequest $request): array
    {
        $updatedItems = [];
        $items = $request->getAllItems();
        if ($items && is_array($items)) {
            foreach ($items as $item) {
                $product = $item->getProduct();
                if ($product) {
                    if ($product->isVirtual() || $item->getParentItem()) {
                        continue;
                    }

                    if ($item->getHasChildren() && $item->isShipSeparately()) {
                        foreach ($item->getChildren() as $child) {
                            $childProduct = $child->getProduct();
                            if (!$childProduct->isVirtual()) {
                                $updatedItems[] = $this->updateItem($child, $childProduct);
                            }
                        }
                    } elseif ($item->getOptionByCode('simple_product')) {
                        $simpleProductOption = $item->getOptionByCode('simple_product');
                        $simpleProduct = $simpleProductOption->getProduct();
                        if ($simpleProduct) {
                            $updatedItems[] = $this->updateItem($item, $simpleProduct);
                        }
                    } else {
                        $updatedItems[] = $this->updateItem($item, $product);
                    }
                }
            }
        } else {
            $updatedItems = $proceed($request);
        }

        return $updatedItems;
    }

    /**
     * Update item
     *
     * @param CartItemInterface $item
     * @param ProductInterface $product
     * @return array
     */
    private function updateItem(CartItemInterface $item, ProductInterface $product): array
    {
        $weight = $product->getWeight();

        $shiphawkLength = $shiphawkWidth = $shiphawkHeight = '';
        $shiphawkLengthCustomAttr = $product->getCustomAttribute('shiphawk_length');
        if ($shiphawkLengthCustomAttr) {
            $shiphawkLength = $shiphawkLengthCustomAttr->getValue() ?? '';
        }

        $shiphawkWidthCustomAttr = $product->getCustomAttribute('shiphawk_width');
        if ($shiphawkWidthCustomAttr) {
            $shiphawkWidth = $shiphawkWidthCustomAttr->getValue() ?? '';
        }

        $shiphawkHeightCustomAttr = $product->getCustomAttribute('shiphawk_height');
        if ($shiphawkHeightCustomAttr) {
            $shiphawkHeight = $shiphawkHeightCustomAttr->getValue() ?? '';
        }

        $updatedItem = [
            'product_sku' => $item->getSku(),
            'quantity' => $item->getQty(),
            'value' => (float)$item->getPrice() ? $item->getPrice() : $product->getPrice(),
            'length' => floatval($shiphawkLength),
            'width' => floatval($shiphawkWidth),
            'height' => floatval($shiphawkHeight),
            'weight' => $weight <= self::ITEM_WEIGHT ? $weight * 16 : $weight,
            'item_type' => $weight <= self::ITEM_WEIGHT ? self::PARCEL_ITEM_TYPE : self::HANDLING_UNIT_ITEM_TYPE,
        ];

        if ($updatedItem['item_type'] === self::HANDLING_UNIT_ITEM_TYPE) {
            $updatedItem['handling_unit_type'] = self::BOX_HANDLING_UNIT_TYPE;
        }

        return $updatedItem;
    }
}
