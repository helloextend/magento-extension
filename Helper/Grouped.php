<?php

namespace Extend\Warranty\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class Grouped extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\GroupedProduct\Model\Product\Type\Grouped
     */
    private $_grouped;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $_productRepository;

    /**
     * Grouped constructor.
     *
     * @param Context $context
     * @param \Magento\GroupedProduct\Model\Product\Type\Grouped $grouped
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\GroupedProduct\Model\Product\Type\Grouped $grouped,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context);
        $this->_grouped = $grouped;
        $this->_productRepository = $productRepository;
    }

    /**
     * @param $warrantyItem
     * @param $item
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isGroupedWarranty($warrantyItem, $item) : bool
    {
        if ($warrantyItem->getOptionByCode('associated_product')->getValue() != $item->getSku()
            && $item->getProductType() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE ) {
            $groupedProductSku = $this->getGroupedWarrantySku($item);
            if ($warrantyItem->getOptionByCode('associated_product')->getValue() == $groupedProductSku) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return string
     */
    public function getGroupedWarrantySku(\Magento\Quote\Model\Quote\Item $item) :string
    {
        $groupedProductsIds = $this->_grouped->getParentIdsByChild($item->getProduct()->getId());
        $groupedProductId  = reset($groupedProductsIds);
        try {
            $groupedProduct = $this->_productRepository->getById($groupedProductId);
        }  catch (NoSuchEntityException $e) {
            return '';
        }

        return $groupedProduct->getSku();
    }

}