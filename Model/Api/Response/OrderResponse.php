<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Response;

use Extend\Warranty\Model\Api\Request\LineItem\AbstractLineItemBuilder;
use \Magento\Framework\DataObject;

class OrderResponse extends DataObject
{
    public function getRawOutput()
    {
        return $this->getData('raw_output');
    }

    public function getId()
    {
        return $this->getData('id');
    }

    public function getLineItems()
    {
        return $this->getData('lineItems');
    }


    /**
     * @param $orderItem
     * @return array|null
     */
    public function getLineItemsByOrderItem($orderItem)
    {
        $lineItemTransactionId = AbstractLineItemBuilder::encodeTransactionId($orderItem);

        $result = [];
        if (!$this->getLineItems()) {
            return $result;
        }

        foreach ($this->getLineItems() as $lineItem) {
            if (isset($lineItem['lineItemTransactionId'])
                && $lineItem['lineItemTransactionId'] == $lineItemTransactionId) {
                $result[] = $lineItem;
            }
        }

        return $result;
    }
}