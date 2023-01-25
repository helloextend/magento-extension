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

use Extend\Warranty\Helper\Data as WarrantyHelper;
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

        $usedWarranties = [];
        foreach ($productItems as $productItem) {
            $warranties = [];

            foreach ($warrantyItems as $warrantyItem) {
                if ($this->checkLeadToken($warrantyItem)) {
                    unset($warrantyItems[$warrantyItem->getItemId()]);
                    continue;
                }

                if (WarrantyHelper::isWarrantyRelatedToQuoteItem($warrantyItem, $productItem)) {
                    $warranties[$warrantyItem->getItemId()] = $warrantyItem;
                    $usedWarranties[$warrantyItem->getItemId()] = $warrantyItem->getItemId();
                }
            }

            $this->normalizeWarrantiesAgainstProductQty($warranties, $productItem->getTotalQty(), $cart, $quote);
        }

        foreach ($usedWarranties as $warrantyItemId) {
            unset($warrantyItems[$warrantyItemId]);
        }

        //removing the warranty items which doesn't have relations
        if (count($warrantyItems)) {
            foreach ($warrantyItems as $warrantyItem) {
                if (!$this->checkLeadToken($warrantyItem)) {
                    $cart->removeItem($warrantyItem->getItemId());
                }
            }
            $cart->save();
        }
    }

    /**
     * Updated warranties qty following
     * related product Total Qty
     * or removes warranties when product qty < warranties qty
     *
     * @param Item[] $warranties
     * @param int $productItemQty
     * @param \Magento\Checkout\Model\Cart $cart
     * @param CartInterface $quote
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function normalizeWarrantiesAgainstProductQty(
        array $warranties,
        int $productItemQty,
        \Magento\Checkout\Model\Cart $cart,
        CartInterface $quote
    ) {
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
     * Checks if warranty item was created via lead token
     *
     * and updated item if it has different qty then in buy request
     *
     * Logic with lead token conditions should be moved to helper if needed somewhere else
     *
     * @param Item $item
     * @return bool
     */
    private function checkLeadToken($item)
    {
        if ($item->getLeadToken() || ($item->getExtensionAttributes() && $item->getExtensionAttributes()->getLeadToken())) {
            $infoBuyRequest = json_decode($item->getOptionByCode('info_buyRequest')->getValue(), 1);

            if (isset($infoBuyRequest['qty']) && $infoBuyRequest['qty'] != $item->getTotalQty()) {
                $this->normalizeWarrantiesAgainstProductQty(
                    [$item],
                    (int)$infoBuyRequest['qty'],
                    $this->cartHelper->getCart(),
                    $item->getQuote()
                );
            }

            return true;
        }
        return false;
    }
}
