<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Block\Adminhtml\Order\View\Items\Renderer;

use \Extend\Warranty\ViewModel\Order\WarrantyItemViewFactory;
use \Extend\Warranty\ViewModel\Order\WarrantyItemView;

class WarrantyRelationInfo extends \Magento\Sales\Block\Adminhtml\Items\AbstractItems
{
    /**
     * @var WarrantyItemView
     */
    protected $warrantyItemView;

    /**
     * @return WarrantyItemView
     */
    public function getWarrantyItemView()
    {
        return $this->warrantyItemView;
    }

    public function __construct(
        \Magento\Backend\Block\Template\Context                   $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface      $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Registry                               $registry,
        WarrantyItemViewFactory                                   $warrantyItemViewFactory,
        array                                                     $data = []
    )
    {
        parent::__construct(
            $context,
            $stockRegistry,
            $stockConfiguration,
            $registry,
            $data
        );

        $this->warrantyItemView = $warrantyItemViewFactory->create();
    }

    /**
     * Get order item from parent block
     *
     * @return \Magento\Sales\Model\Order\Item
     * @codeCoverageIgnore
     */
    public function getItem()
    {
        return $this->getParentBlock()->getData('item');
    }
}