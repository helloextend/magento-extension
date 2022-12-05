<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */
namespace Extend\Warranty\Plugin\Sales\Model;

use Extend\Warranty\Model\Product\Type;
use Magento\Sales\Model\Order;

class OrderPlugin
{

    /**
     *
     * Do not allow to reorder order if it has warranty items
     *
     * @param Order $subject
     * @param $result
     * @return void
     */
    public function afterCanReorder($subject, $result)
    {
        if ($result) {
            $itemsCollection = $subject->getItemsCollection();
            /** @var Order\Item $item */
            foreach ($itemsCollection as $item) {
                if ($item->getProductType() == Type::TYPE_CODE) {
                    $result = false;
                }
            }
        }

        return $result;
    }
}