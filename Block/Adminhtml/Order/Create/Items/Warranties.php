<?php

namespace Extend\Warranty\Block\Adminhtml\Order\Create\Items;

class Warranties extends \Magento\Backend\Block\Template
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
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\GroupedProduct\Model\Product\Type\Grouped $grouped
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\GroupedProduct\Model\Product\Type\Grouped $grouped,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        array $data = []
    )
    {
        parent::__construct($context, $data);

        $this->_grouped = $grouped;
        $this->_productRepository = $productRepository;
    }

    /**
     * Get order item
     *
     * @return \Magento\Quote\Model\Quote\Item
     * @codeCoverageIgnore
     */
    public function getItem()
    {
        return $this->getParentBlock()->getData('item');
    }

    /**
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getParentIdsByChild()
    {
        $item = $this->getItem();
        $groupedProductsIds = $this->_grouped->getParentIdsByChild($item->getProduct()->getId());
        if ($groupedProductsIds) {
            $groupedProductId = reset($groupedProductsIds);
            $groupedProduct = $this->_productRepository->getById($groupedProductId);
            return $groupedProduct->getSku();
        }

        return null;
    }
}
