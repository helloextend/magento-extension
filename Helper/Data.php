<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Helper;

use Extend\Warranty\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Exception;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Api\Data\OrderInterface;
use \Magento\Bundle\Model\Product\Type as BundleProductType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProductType;

/**
 * Class Data
 *
 * Warranty Helper
 */
class Data
{
    /**
     * `Contract ID` field
     */
    public const CONTRACT_ID = 'contract_id';

    /**
     * Cron regular expressions
     */
    public const CRON_REG_EXP = '/^(?:[1-9]?\d|\*)(?:(?:[\/-][1-9]?\d)|(?:,[1-9]?\d)+)?$/';

    /**
     * List of not allowed product types
     */
    public const NOT_ALLOWED_TYPES = [
        Type::TYPE_CODE,
    ];

    /**
     * Json serializer Model
     *
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * Data constructor.
     *
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(JsonSerializer $jsonSerializer)
    {
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Format price
     *
     * @param string|int|float|null $price
     * @return float
     */
    public function formatPrice($price): float
    {
        if (empty($price)) {
            return 0;
        }

        $floatPrice = (float) $price;

        $formattedPrice = number_format(
            $floatPrice,
            2,
            '',
            ''
        );

        return (float) $formattedPrice;
    }

    /**
     * removeFormatPrice
     *
     * @param int|null $price
     * @return float
     */
    public function removeFormatPrice(?int $price): float
    {
        $price = (string)$price;

        $price = substr_replace(
            $price,
            '.',
            strlen($price) - 2,
            0
        );

        return (float) $price;
    }

    /**
     * Check if cron schedule expression is valid
     *
     * @param string $cronExpressionString
     * @return bool
     */
    public function isCronExpressionValid(string $cronExpressionString): bool
    {
        $cronExprArray = explode(' ', $cronExpressionString);
        foreach ($cronExprArray as $cronExp) {
            if (!preg_match(self::CRON_REG_EXP, $cronExp)) {
                $isValid = false;
                break;
            }
        }

        return $isValid ?? count($cronExprArray) === 5;
    }

    /**
     * Decode data
     *
     * @param string|null $data
     *
     * @return string|null
     */
    public function unserialize($data)
    {
        try {
            $result = $this->jsonSerializer->unserialize($data);
        } catch (Exception $exception) {
            $result = null;
        }

        return $result;
    }

    /**
     * Get store name
     *
     * @param OrderInterface $order
     *
     * @return string
     */
    public function getCustomerFullName(OrderInterface $order)
    {
        $firstName = $order->getCustomerFirstname();
        $lastName = $order->getCustomerLastname();

        if (empty($firstName) || empty($lastName)) {
            $billingAddress = $order->getBillingAddress();
            $shippingAddress = $order->getShippingAddress();

            if (empty($firstName) && $shippingAddress) {
                $firstName = $shippingAddress->getFirstname();

                if (empty($firstName) && $billingAddress) {
                    $firstName = $billingAddress->getFirstname() ?? '';
                }
            }

            if (empty($lastName) && $shippingAddress) {
                $lastName = $shippingAddress->getLastname();

                if (empty($lastName) && $billingAddress) {
                    $lastName = $billingAddress->getLastname() ?? '';
                }
            }
        }

        return $firstName . ' ' . $lastName;
    }


    /**
     * Return true if quote Item is related to warranty
     *
     * @param Item $warrantyItem
     * @param Item $quoteItem
     * @return bool
     */
    static public function isWarrantyRelatedToQuoteItem(Item $warrantyItem, Item $quoteItem, $checkWithChildren = false): bool
    {

        if($checkWithChildren === true && $quoteItem->getChildren()){
            foreach($quoteItem->getChildren() as $child){
                if(self::isWarrantyRelatedToQuoteItem($warrantyItem,$child)){
                    return true;
                }
            }
        }
        $associatedProductSku = $warrantyItem->getOptionByCode(Type::ASSOCIATED_PRODUCT);

        $relatedSkus = [$associatedProductSku->getValue()];

        if ($dynamicSku = $warrantyItem->getOptionByCode(Type::DYNAMIC_SKU)) {
            $relatedSkus[] = $dynamicSku->getValue();
        }

        $itemSku = self::getComplexProductSku($quoteItem->getProduct());
        $skuCheck = in_array($itemSku, $relatedSkus);

        if ($relatedItemOption = $warrantyItem->getOptionByCode(Type::RELATED_ITEM_ID)) {
            $relatedCheck = in_array($relatedItemOption->getValue(), [$quoteItem->getId(), $quoteItem->getParentItemId()]);
        } else {
            // if no related id specified then skip it
            $relatedCheck = true;
        }

        /**
         * "relatedItemId" check should avoid situation when two quote item
         * has same sku but connected to different warranty items.
         *
         * This case possible with bundles, when two different bundle could
         * have same warrantable children
         */
        return $relatedCheck && $skuCheck;
    }

    /**
     * Return sku for a product to be used as associated option in warranty
     *
     * For bundle return dynamic sku even if product has "Fixed" Sku type
     *
     * For configurable it will return child sku value not main Product Sku
     *
     * the rest will get sku from ->data['sku']
     *
     * @param Product $product
     * @return string
     */
    static public function getComplexProductSku($product)
    {
        /**
         * If configurable we need child SKU
         */
        if($product->getTypeId() == ConfigurableProductType::TYPE_CODE){
            return $product->getSku();
        }

        /**
         * For bundle we need dynamic sku even if configured as fixed sku
         */
        if($product->getTypeId() == BundleProductType::TYPE_CODE){
            $productClone = clone $product;
            $productClone->setData('sku_type', 0);
            return $productClone->getSku();
        }

        return $product->getData('sku');
    }
}
