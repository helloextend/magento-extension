<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\ViewModel\Order;

use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Model\WarrantyRelation;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class WarrantyItemView implements ArgumentInterface
{

    /**
     * @var WarrantyRelation
     */
    protected $warrantyRelation;

    /**
     * @var OrderItemInterface | null
     */
    protected $associatedItems = [];

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param WarrantyRelation $warrantyRelation
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        WarrantyRelation           $warrantyRelation,
        ProductRepositoryInterface $productRepository
    )
    {
        $this->productRepository = $productRepository;
        $this->warrantyRelation = $warrantyRelation;
    }

    /**
     * Return associated warrantable item name
     *
     * @param $warrantyItem
     * @param $order
     * @return string
     */
    public function getAssociatedItemName($warrantyItem, $order): string
    {
        $associatedItemName = '';

        if ($warrantyItem->getLeadToken()) {
            $associatedItemName = $this->getAssociatedProductName($warrantyItem);
        } elseif ($associatedItem = $this->getAssociatedOrderItem($warrantyItem, $order)) {
            $associatedItemName = $associatedItem->getName();
        }

        return $associatedItemName;
    }

    /**
     * Return associated warrantable item name
     *
     * @return string
     */
    public function getAssociatedItemSku($warrantyItem, $order): string
    {
        $associatedProductSku = '';

        if ($warrantyItem->getLeadToken()) {
            $associatedProductSku = $warrantyItem->getProductOptionByCode(Type::ASSOCIATED_PRODUCT);
        } elseif ($associatedItem = $this->getAssociatedOrderItem($warrantyItem, $order)) {
            $associatedProductSku = $this->warrantyRelation->getRelationOrderItemSku($associatedItem);
        }

        return $associatedProductSku;
    }

    /**
     * @return OrderItemInterface|null
     */
    public function getAssociatedOrderItem($warrantyItem, $order)
    {
        if (!isset($this->associatedItems[$warrantyItem->getId()])) {
            foreach ($order->getAllItems() as $orderItem) {
                if ($orderItem->getProductType() == Type::TYPE_CODE) {
                    continue;
                }
                if ($this->warrantyRelation->isWarrantyRelatedToOrderItem($warrantyItem, $orderItem)) {
                    $this->associatedItems[$warrantyItem->getId()] = $orderItem;
                    break;
                }
            }
        }
        return $this->associatedItems[$warrantyItem->getId()] ?? null;
    }

    /**
     * @param $warrantyItem
     * @return string|null
     */
    protected function getAssociatedProductName($warrantyItem)
    {
        $associatedProductSku = $warrantyItem->getProductOptionByCode(Type::ASSOCIATED_PRODUCT);
        try {
            $associatedProduct = $this->productRepository->get($associatedProductSku);
            $associatedProductName = $associatedProduct->getName();
        } catch (NoSuchEntityException $e) {
            //muting exception in case when product was deleted from catalog
            $associatedProductName = '';
        }
        return $associatedProductName;
    }
}