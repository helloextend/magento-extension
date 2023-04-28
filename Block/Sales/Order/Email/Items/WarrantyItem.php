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

use Extend\Warranty\ViewModel\Order\WarrantyItemView;
use Extend\Warranty\ViewModel\Order\WarrantyItemViewFactory;
use Magento\Framework\View\Element\Template\Context;

class WarrantyItem extends \Magento\Sales\Block\Order\Email\Items\Order\DefaultOrder
{
    /**
     * @var WarrantyItemView
     */
    protected $warrantyItemView;

    /**
     * @param Context $context
     * @param WarrantyItemViewFactory $warrantyItemView
     * @param array $data
     */
    public function __construct(
        Context                 $context,
        WarrantyItemViewFactory $warrantyItemViewFactory,
        array                   $data = [])
    {
        parent::__construct($context, $data);
        $this->warrantyItemView = $warrantyItemViewFactory->create();
    }

    /**
     * Return associated warrantable item name
     *
     * @return string
     */
    public function getAssociatedItemName(): string
    {
        return $this->warrantyItemView->getAssociatedItemName(
            $this->getItem(),
            $this->getOrder()
        );
    }
}