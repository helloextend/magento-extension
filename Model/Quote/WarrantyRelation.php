<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Quote;

use Extend\Warranty\Model\Product\Type;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationInterface;


class WarrantyRelation implements RelationInterface
{

    /**
     * Once quote_item updated and item_id changed in Quote::updateItem()
     * we need to change related_item_id for warranty quote item to keep this relation
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return void
     */
    public function processRelation(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->itemsCollectionWasSet()) {
            foreach ($object->getItemsCollection() as $item) {
                if ($item->getProductType() == Type::TYPE_CODE && $item->hasRelatedItem()) {
                    $item->getOptionByCode(Type::RELATED_ITEM_ID)
                        ->setValue($item->getRelatedItem()->getId());
                    $item->saveItemOptions();
                    $item->unsetRelatedItem();
                }
            }
        }
    }
}