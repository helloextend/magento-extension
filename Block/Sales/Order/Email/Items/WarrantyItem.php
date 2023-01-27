<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Block\Sales\Order\Email\Items;

use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Model\WarrantyRelation;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderItemInterface;

class WarrantyItem extends \Magento\Sales\Block\Order\Email\Items\Order\DefaultOrder
{

    /**
     * @var WarrantyRelation
     */
    protected $warrantyRelation;

    /**
     * @var OrderItemInterface | null
     */
    protected $associatedItem;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param Context $context
     * @param WarrantyRelation $warrantyRelation
     * @param ProductRepositoryInterface $productRepository
     * @param array $data
     */
    public function __construct(
        Context                    $context,
        WarrantyRelation           $warrantyRelation,
        ProductRepositoryInterface $productRepository,
        array                      $data = [])
    {
        parent::__construct($context, $data);
        $this->productRepository = $productRepository;
        $this->warrantyRelation = $warrantyRelation;
    }

    /**
     * Return associated warrantable item name
     *
     * @return string
     */
    public function getAssociatedItemName(): string
    {
        $warrantyItem = $this->getItem();

        $associatedItemName = '';

        if ($warrantyItem->getLeadToken()) {
            $associatedItemName = $this->getAssociatedProductName($warrantyItem);
        } elseif ($associatedItem = $this->getAssociatedOrderItem()) {
            $associatedItemName = $associatedItem->getName();
        }

        return $associatedItemName;
    }

    /**
     * @return OrderItemInterface|null
     */
    public function getAssociatedOrderItem()
    {
        $warrantyItem = $this->getItem();
        if ($this->associatedItem === null) {
            foreach ($this->getOrder()->getAllItems() as $orderItem) {
                if ($orderItem->getProductType() == Type::TYPE_CODE) {
                    continue;
                }
                if ($this->warrantyRelation->isWarrantyRelatedToOrderItem($warrantyItem, $orderItem)) {
                    $this->associatedItem = $orderItem;
                    break;
                }
            }
        }
        return $this->associatedItem;
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
            //muting ex in case when product was deleted from catalog and email is sending from admin console
            $associatedProductName = '';
        }
        return $associatedProductName;
    }
}