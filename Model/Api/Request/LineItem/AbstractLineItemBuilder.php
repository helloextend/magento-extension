<?php
/**
 * @author     Guidance Magento Team <magento@guidance.com>
 * @copyright  Copyright (c) 2023 Guidance Solutions (http://www.guidance.com)
 */

namespace Extend\Warranty\Model\Api\Request\LineItem;

use Extend\Warranty\Helper\Data;
use Extend\Warranty\Model\Api\Request\ProductDataBuilderFactory;
use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Model\WarrantyRelation;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderItemInterface;

class AbstractLineItemBuilder
{
    protected $warrantyRelation;
    protected $helper;
    protected $productDataBuilderFactory;
    protected $productRepository;

    /**
     * @param WarrantyRelation $warrantyRelation
     * @param Data $helper
     * @param ProductDataBuilderFactory $productDataBuilderFactory
     */
    public function __construct(
        WarrantyRelation           $warrantyRelation,
        Data                       $helper,
        ProductDataBuilderFactory  $productDataBuilderFactory,
        ProductRepositoryInterface $productRepository
    )
    {
        $this->warrantyRelation = $warrantyRelation;
        $this->helper = $helper;
        $this->productRepository = $productRepository;
        $this->productDataBuilderFactory = $productDataBuilderFactory;
    }

    /**
     * Get plan
     *
     * @param OrderItemInterface $orderItem
     * @return array
     */
    protected function getPlan(OrderItemInterface $orderItem): array
    {
        $warrantyId = $orderItem->getProductOptionByCode(Type::WARRANTY_ID);
        $warrantyId = is_array($warrantyId) ? array_shift($warrantyId) : $warrantyId;

        $plan = [
            'purchasePrice' => $this->helper->formatPrice($orderItem->getPrice()),
            'id' => $warrantyId,
        ];

        return $plan;
    }

    /**
     * Get product
     *
     * @param string $sku
     * @return ProductInterface|null
     */
    protected function getCatalogProduct(string $sku)
    {
        try {
            $product = $this->productRepository->get($sku);
        } catch (LocalizedException $e) {
            $product = null;
        }

        return $product;
    }
}