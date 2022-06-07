<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Plugin\Quote\Item;

use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Extend\Warranty\Helper\Api\Magento\Data;

/**
 * Class ItemDataPlugin
 *
 * ItemDataPlugin plugin
 */
class ItemDataPlugin
{
    /**
     * Inject item extension attributes into quote item data.
     *
     * @param CartItemRepositoryInterface $itemRepository
     * @param CartItemInterface $item
     */
    public function beforeSave(CartItemRepositoryInterface $itemRepository, CartItemInterface $item)
    {
        $extensionAttributes = $item->getExtensionAttributes();
        $leadToken = $extensionAttributes->getLeadToken();

        $item->setData(Data::LEAD_TOKEN, $leadToken);
    }

    /**
     * Inject item data into quote items extension attributes.
     *
     * @param CartItemRepositoryInterface $itemRepository
     * @param array $items
     * @return CartItemInterface[]
     */
    public function afterGetList(CartItemRepositoryInterface $itemRepository, $items)
    {
        foreach ($items as $item) {
            $leadToken = $item->getData(Data::LEAD_TOKEN);

            $extensionAttributes = $item->getExtensionAttributes();
            $extensionAttributes->setLeadToken($leadToken);
            $item->setExtensionAttributes($extensionAttributes);
        }

        return $items;
    }
}
