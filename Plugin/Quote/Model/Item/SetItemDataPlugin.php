<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Plugin\Quote\Model\Item;

use Magento\Quote\Api\Data\CartItemExtensionInterfaceFactory;
use Magento\Quote\Model\Quote\Item;
use Extend\Warranty\Helper\Api\Magento\Data;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Exception;

/**
 * Class SetItemDataPlugin
 *
 * SetItemDataPlugin plugin
 */
class SetItemDataPlugin
{
    /**
     * Json serializer
     *
     * @var  JsonSerializer
     */
    private $serializer;

    /**
     * Cart extension factory
     *
     * @var CartItemExtensionInterfaceFactory
     */
    private $extensionFactory;

    /**
     * SetItemDataPlugin constructor.
     *
     * @param JsonSerializer $serializer
     * @param CartItemExtensionInterfaceFactory $extensionFactory
     */
    public function __construct(
        JsonSerializer $serializer,
        CartItemExtensionInterfaceFactory $extensionFactory
    ) {
        $this->serializer = $serializer;
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * Added item data after set Product
     *
     * @param Item $item
     * @param Item $result
     * @return Item
     */
    public function afterSetProduct(Item $item, Item $result): Item
    {
        $product = $result->getProduct();

        if ($result->getSku() && $product) {
            $buyRequestValues = [];
            $buyRequest = $product->getCustomOption('info_buyRequest');

            if ($buyRequest && $buyRequest->getValue()) {
                $buyRequestValues = $this->getBuyRequestValues($buyRequest->getValue());
            }

            if ($buyRequestValues && isset($buyRequestValues['leadToken'])) {
                $leadToken = $buyRequestValues['leadToken'];
            } else {
                $leadToken = '';
            }

            $extensionAttributes = $result->getExtensionAttributes();

            if ($extensionAttributes === null) {
                $extensionAttributes = $this->extensionFactory->create();
            }

            if ($leadToken) {
                $extensionAttributes->setData(Data::LEAD_TOKEN, $leadToken);
            }

            $result->setExtensionAttributes($extensionAttributes);
        }

        return $result;
    }

    /**
     * Added item data before save
     *
     * @param Item $item
     * @return Item
     */
    public function afterBeforeSave(Item $item): Item
    {
        $extensionAttributes = $item->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();
        }

        $leadToken = $extensionAttributes->getLeadToken();
        $item->setData(Data::LEAD_TOKEN, $leadToken);

        return $item;
    }

    /**
     * Get BuyRequest
     *
     * @param string $buyRequestJson
     * @return array
     */
    private function getBuyRequestValues(string $buyRequestJson): array
    {
        try {
            $buyRequestValues = $this->serializer->unserialize($buyRequestJson);
        } catch (Exception $exception) {
            $buyRequestValues = [];
        }

        return $buyRequestValues;
    }
}
