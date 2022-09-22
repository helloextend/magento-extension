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
            $sku = $productItem->getSku();
            $warranties = [];

            $product = $productItem->getProduct();

            if ($product->hasCustomOptions() && $product->getTypeId() === \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
                $sku = $product->getData('sku');
            }

            foreach ($warrantyItems as $warrantyItem) {
                if (!empty($warrantyItem->getLeadToken())) {
                    continue;
                }

                $associatedProductOption = $warrantyItem->getOptionByCode(Type::ASSOCIATED_PRODUCT);

                if ($associatedProductOption && $associatedProductOption->getValue()) {
                    $associatedSku = $associatedProductOption->getValue();
                    if ($sku === $associatedSku
                        && (
                            $productItem->getProductType() === Configurable::TYPE_CODE
                            || null === $productItem->getOptionByCode('parent_product_id')
                        )
                    ) {
                        $warranties[$warrantyItem->getItemId()] = $warrantyItem;
                    }
                }
            }

            $productItemQty = $productItem->getQty();
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
}
