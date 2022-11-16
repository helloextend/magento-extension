<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Plugin\Quote\Model;

use Extend\Warranty\Model\Product\Type;
use Magento\Quote\Model\Quote\Item;

class QuotePlugin
{
    /**
     * Quote::updateItem could recreate quote_item and it would brake connection between warranty and warrantable item
     * So we are saving new related item to warranty to save related_item_id
     * afterwords in \Extend\Warranty\Model\Quote\WarrantyRelation::processRelation
     *
     * @param \Magento\Quote\Model\Quote $subject
     * @param \Magento\Quote\Model\Quote\Item $result
     * @param integer $itemId
     * @param $buyRequest
     * @param $params
     * @return \Magento\Quote\Model\Quote\Item
     */
    public function afterUpdateItem(\Magento\Quote\Model\Quote $subject, $result, $itemId, $buyRequest, $params = null)
    {
        if ($result->getId() !== $itemId) {
            /** @var Item $item */
            foreach ($subject->getAllItems() as $item) {
                if ($item->getProductType() != Type::TYPE_CODE) {
                    continue;
                }
                if ($item->getOptionByCode(Type::RELATED_ITEM_ID)
                    && $itemId == $item->getOptionByCode(Type::RELATED_ITEM_ID)->getValue()
                ) {
                    $item->setRelatedItem($result);
                }
            }
        }
        return $result;
    }
}