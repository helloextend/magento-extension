<?php
/**
 * Extend Warranty.
 *
 * @author      Extend Magento Team <magento@guidance.com>
 *
 * @category    Extend
 *
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Block\Adminhtml\Order\Create\Items;

use Magento\Backend\Block\Template;
use Magento\Quote\Model\Quote\Item;

/**
 * Class Warranties.
 *
 * Get order item object from parent block
 */
class Warranties extends Template
{
    /**
     * Get order item object from parent block.
     *
     * @return Item
     */
    public function getItem()
    {
        $parentBlock = $this->getParentBlock();

        return $parentBlock->getItem();
    }
}
