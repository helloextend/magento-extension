<?php

namespace Extend\Warranty\Block\Adminhtml\Order\Create\Items;

class Warranties extends \Magento\Backend\Block\Template
{

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
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
}
