<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Request\LineItem;

use Extend\Warranty\Helper\Data;
use Extend\Warranty\Model\Api\Request\ProductDataBuilder;
use Extend\Warranty\Model\Api\Request\ProductDataBuilderFactory;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Model\WarrantyRelation;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderItemInterface;

class AbstractLineItemBuilder
{
    /**
     * @var WarrantyRelation
     */
    protected $warrantyRelation;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ProductDataBuilderFactory
     */
    protected $productDataBuilderFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @param WarrantyRelation $warrantyRelation
     * @param Data $helper
     * @param DataHelper $dataHelper
     * @param ProductDataBuilderFactory $productDataBuilderFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        WarrantyRelation           $warrantyRelation,
        Data                       $helper,
        DataHelper                 $dataHelper,
        ProductDataBuilderFactory  $productDataBuilderFactory,
        ProductRepositoryInterface $productRepository
    )
    {
        $this->warrantyRelation = $warrantyRelation;
        $this->helper = $helper;
        $this->dataHelper = $dataHelper;
        $this->productRepository = $productRepository;
        $this->productDataBuilderFactory = $productDataBuilderFactory;
    }

    public function preparePayload($item)
    {
        return ['lineItemTransactionId' => $this->encodeTransactionId($item)];
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

    /**
     * @param $item
     * @return mixed
     */
    protected function validate($item)
    {
        return false;
    }

    /**
     * @param $orderItem
     * @return []
     */
    protected function getProductPayload($orderItem)
    {
        $product = $this->getCatalogProduct(
            $this->warrantyRelation->getOfferOrderItemSku(
                $orderItem
            )
        );

        if (!$product) {
            return [];
        }

        /** @var ProductDataBuilder $productDataBuilder */
        $productDataBuilder = $this->productDataBuilderFactory->create();

        $productPayload = $productDataBuilder->preparePayload($product);
        $productPayload['listPrice'] = $this->helper->formatPrice(
            $productDataBuilder->calculateSyncProductPrice($product)
        );
        $productPayload['purchasePrice'] = $this->helper->formatPrice(
            $orderItem->getRowTotal() / $orderItem->getQtyOrdered()
        );

        $productPayload['id'] = $product->getSku();

        return $productPayload;
    }

    /**
     * @param $orderItem
     * @return string
     */
    public static function encodeTransactionId($orderItem)
    {
        return $orderItem->getOrderId() . ':' . $orderItem->getId();
    }
}