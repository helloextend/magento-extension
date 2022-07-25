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

use Magento\Quote\Api\GuestCartItemRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemExtensionFactory;
use Extend\Warranty\Helper\Api\Magento\Data;

/**
 * Class GuestItemDataPlugin
 *
 * GuestItemDataPlugin plugin
 */
class GuestItemDataPlugin
{
    /**
     * @var CartItemExtensionFactory
     */
    private $cartItemExtensionFactory;

    /**
     * @param CartItemExtensionFactory $cartItemExtensionFactory
     */
    public function __construct(CartItemExtensionFactory $cartItemExtensionFactory)
    {
        $this->cartItemExtensionFactory = $cartItemExtensionFactory;
    }

    /**
     * Inject item extension attributes into quote item data.
     *
     * @param GuestCartItemRepositoryInterface $itemRepository
     * @param CartItemInterface $item
     */
    public function beforeSave(GuestCartItemRepositoryInterface $itemRepository, CartItemInterface $item)
    {
        $extensionAttributes = $item->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->cartItemExtensionFactory->create();
        }

        $leadToken = $extensionAttributes->getLeadToken();

        $item->setData(Data::LEAD_TOKEN, $leadToken);
    }

    /**
     * Inject item data into quote items extension attributes.
     *
     * @param GuestCartItemRepositoryInterface $itemRepository
     * @param array $items
     * @return CartItemInterface[]
     */
    public function afterGetList(GuestCartItemRepositoryInterface $itemRepository, $items)
    {
        foreach ($items as $item) {
            $leadToken = $item->getData(Data::LEAD_TOKEN);

            $extensionAttributes = $item->getExtensionAttributes();

            if ($extensionAttributes === null) {
                $extensionAttributes = $this->cartItemExtensionFactory->create();
            }

            $extensionAttributes->setLeadToken($leadToken);
            $item->setExtensionAttributes($extensionAttributes);
        }

        return $items;
    }
}
