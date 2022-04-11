<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

declare(strict_types=1);

namespace Extend\Warranty\Plugin\Quote\Model\Item;

use Magento\Quote\Model\Quote\Item;
use Extend\Warranty\Helper\Api\Magento\Data;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Exception;

/**
 * Class SetItemDataPlugin
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
     * SetItemDataPlugin constructor.
     *
     * @param JsonSerializer $serializer
     */
    public function __construct(
        JsonSerializer $serializer
    ) {
        $this->serializer = $serializer;
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
            $buyRequest = $product->getCustomOption('info_buyRequest');

            if ($buyRequest->getValue()) {
                $buyRequestValues = $this->getBuyRequestValues($buyRequest->getValue());
            }

            if ($buyRequestValues && isset($buyRequestValues['leadToken'])) {
                $leadToken = $buyRequestValues['leadToken'];
            } else {
                $leadToken = '';
            }

            $extensionAttributes = $result->getExtensionAttributes();

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
