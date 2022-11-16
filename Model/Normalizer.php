<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model;

use Extend\Warranty\Helper\Tracking as TrackingHelper;
use Extend\Warranty\Model\Product\Type;
use InvalidArgumentException;
use Magento\Checkout\Helper\Cart as CartHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemExtension;
use Magento\Quote\Model\Quote\Item;

/**
 * Class Normalizer
 *
 * Warranty Normalizer Model
 */
class Normalizer
{
    /**
     * Warranty Tracking Helper
     *
     * @var TrackingHelper
     */
    private $trackingHelper;

    /**
     * Json Serializer Model
     *
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * Cart Item Repository Model
     *
     * @var CartItemRepositoryInterface
     */
    private $quoteItemRepository;

    /**
     * Cart Helper Model
     *
     * @var CartHelper
     */
    private $cartHelper;

    /**
     * Normalizer constructor
     *
     * @param TrackingHelper $trackingHelper
     * @param JsonSerializer $jsonSerializer
     * @param CartItemRepositoryInterface $quoteItemRepository
     * @param CartHelper $cartHelper
     */
    public function __construct(
        TrackingHelper $trackingHelper,
        JsonSerializer $jsonSerializer,
        CartItemRepositoryInterface $quoteItemRepository,
        CartHelper $cartHelper
    ) {
        $this->trackingHelper = $trackingHelper;
        $this->jsonSerializer = $jsonSerializer;
        $this->quoteItemRepository = $quoteItemRepository;
        $this->cartHelper = $cartHelper;
    }

    /**
     * Normalize quote
     *
     * @param CartInterface $quote
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function normalize(CartInterface $quote)
    {
        $productItems = $warrantyItems = [];

        $cart = $this->cartHelper->getCart();
        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getProductType() === Type::TYPE_CODE) {
                $warrantyItems[$quoteItem->getItemId()] = $quoteItem;
            } else {
                $productItems[$quoteItem->getItemId()] = $quoteItem;
            }
        }

        foreach ($productItems as $productItem) {
            $warranties = [];

            $product = $productItem->getProduct();

            foreach ($warrantyItems as $warrantyItem) {

                if ($this->checkLeadToken($warrantyItem)) {
                    unset($warrantyItems[$warrantyItem->getItemId()]);
                    continue;
                }

                if ($this->isWarrantyQuoteItemMatch($warrantyItem, $productItem)) {
                    $warranties[$warrantyItem->getItemId()] = $warrantyItem;
                    unset($warrantyItems[$warrantyItem->getItemId()]);
                }
            }

            if ($productItem->getProductType() === 'bundle') {
                $productItemQty = $productItem->getQty();
                foreach ($warranties as $warrantyItem) {
                    $associatedProductOption = $warrantyItem->getOptionByCode(Type::ASSOCIATED_PRODUCT);
                    $dynamicSku = $warrantyItem->getOptionByCode(Type::DYNAMIC_SKU);
                    if ($dynamicSku && $associatedSku = $dynamicSku->getValue()) {
                        if ($associatedSku === $product->getData('sku')) {
                            $this->normalizeWarrantiesAgainstProductQty([$warrantyItem], $productItemQty, $cart, $quote);
                            unset($warranties[$warrantyItem->getItemId()]);
                        }
                    } else if ($associatedProductOption && $associatedSku = $associatedProductOption->getValue()) {
                        foreach ($productItem->getChildren() as $item) {
                            if ($item->getProduct()->getData('sku') === $associatedSku) {
                                if ($qty = $item->getQty()) {
                                    $this->normalizeWarrantiesAgainstProductQty([$warrantyItem], $productItemQty * $qty, $cart, $quote);
                                    unset($warranties[$warrantyItem->getItemId()]);
                                    break;
                                }
                            }
                        }
                    }
                }

                if (count($warranties)) {
                    foreach ($warranties as $warranty) {
                        $cart->removeItem($warranty->getItemId());
                    }
                    $cart->save();
                }
            } else {
                $this->normalizeWarrantiesAgainstProductQty($warranties, $productItem->getTotalQty(), $cart, $quote);
            }
        }

        //removing the warranty items which doesn't have relations
        if (count($warrantyItems)) {
            if ($warrantyItems) {
                foreach ($warrantyItems as $warrantyItem) {
                    if (!$this->checkLeadToken($warrantyItem)) {
                        $cart->removeItem($warrantyItem->getItemId());
                    }
                }
                $cart->save();
            }
        }
    }

    private function normalizeWarrantiesAgainstProductQty(array $warranties, int $productItemQty, \Magento\Checkout\Model\Cart $cart, CartInterface $quote) {
        if (count($warranties) > 1) {
            $warrantyItemsQty = $this->getWarrantyItemsQty($warranties);
            if ($productItemQty > $warrantyItemsQty) {
                $sortedWarranties = $this->sortWarrantyItemsByPrice($warranties);
                $warranty = array_shift($sortedWarranties);
                $updatedWarrantyItemsQty = $this->getWarrantyItemsQty($sortedWarranties);
                $warranty->setQty($productItemQty - $updatedWarrantyItemsQty);
                $this->quoteItemRepository->save($warranty);
            } elseif ($productItemQty < $warrantyItemsQty) {
                $sortedWarranties = $this->sortWarrantyItemsByPrice($warranties, SortOrder::SORT_DESC);
                $delta = $warrantyItemsQty - $productItemQty;
                do {
                    $warranty = array_shift($sortedWarranties);
                    $warrantyQty = $warranty->getQty();
                    if ($warrantyQty > $delta) {
                        $warranty->setQty($warrantyQty - $delta);
                        $this->quoteItemRepository->save($warranty);
                    } else {
                        $cart->removeItem($warranty->getItemId());
                        $quote->setTotalsCollectedFlag(false);
                        $cart->save();
                    }
                    $delta -= $warrantyQty;
                } while ($delta > 0);
            }
        } elseif (count($warranties) === 1) {
            $warranty = array_shift($warranties);
            if ($productItemQty !== $warranty->getQty()) {
                $warranty->setQty($productItemQty);
                $this->quoteItemRepository->save($warranty);
            }
        }
    }

    /**
     * Sort warranty items by price
     *
     * @param array $warrantyItems
     * @param string $sortDirection
     * @return array
     */
    private function sortWarrantyItemsByPrice(
        array $warrantyItems,
        string $sortDirection = SortOrder::SORT_ASC
    ): array {
        $prices = [];
        foreach ($warrantyItems as $warrantyItem) {
            $buyRequest = $warrantyItem->getOptionByCode(Type::BUY_REQUEST);
            if ($buyRequest && $buyRequest->getValue()) {
                $buyRequestJsonValue = $buyRequest->getValue();

                try {
                    $buyRequestValue = $this->jsonSerializer->unserialize($buyRequestJsonValue);
                } catch (InvalidArgumentException $exception) {
                    $buyRequestValue = [];
                }

                if (!empty($buyRequestValue)) {
                    $prices[$buyRequest->getItemId()] = (int)$buyRequestValue['price'];
                }
            }
        }

        if ($sortDirection === SortOrder::SORT_ASC) {
            arsort($prices, SORT_NUMERIC);
        } else {
            asort($prices, SORT_NUMERIC);
        }

        $warrantyItemsByPrice = [];
        foreach ($prices as $key => $value) {
            $warrantyItemsByPrice[] = $warrantyItems[$key];
        }

        return $warrantyItemsByPrice;
    }

    /**
     * Get qty of warranty items
     *
     * @param array $warrantyItems
     * @return float
     */
    private function getWarrantyItemsQty(array $warrantyItems): float
    {
        $qty = 0;
        foreach ($warrantyItems as $warrantyItem) {
            $qty += $warrantyItem->getQty();
        }

        return $qty;
    }

    /**
     * @param Item $item
     * @return bool
     */
    private function checkLeadToken($item)
    {
        if ($item->getLeadToken() || ($item->getExtensionAttributes() && $item->getExtensionAttributes()->getLeadToken())) {
            return true;
        }
        return false;
    }

    protected function isWarrantyQuoteItemMatch($warrantyItem, $quoteItem)
    {
        $relatedItemOption = $warrantyItem->getOptionByCode(Type::RELATED_ITEM_ID);
        $associatedProductSku = $warrantyItem->getOptionByCode(Type::ASSOCIATED_PRODUCT);

        if ($relatedItemOption) {
            $relatedCheck = in_array($relatedItemOption->getValue(), [$quoteItem->getId(), $quoteItem->getParentItemId()]);
        } else {
            // if no related id specified lets skip it
            $relatedCheck = true;
        }

        /**
         * "relatedItemId" check should avoid situation when two quote item
         * has same sku but connected to different warranty items.
         *
         * This case possible with bundles, when two different bundle could
         * have same warrantable children
         */
        return $relatedCheck && $quoteItem->getSku() == $associatedProductSku->getValue();
    }
}
