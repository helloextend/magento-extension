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

use Extend\Warranty\Model\Product\Type;
use JMS\Serializer\Tests\Fixtures\Discriminator\Car;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item;

class WarrantyRelation
{
    const DEFAULT_SKU_PROCESSOR = 'default';

    /**
     * @var RelationProcessorInterface[]
     */
    private array $relationProcessors = [];

    protected $checkoutSession;

    /**
     * @param RelationProcessorInterface[] $relationProcessors
     */
    public function __construct(
        Session $checkoutSession,
                $relationProcessors = []
    )
    {
        $this->relationProcessors = $relationProcessors;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param Item $warrantyItem
     * @param Item $quoteItem
     * @param $checkWithChildren
     * @return bool
     */
    public function isWarrantyRelatedToQuoteItem(Item $warrantyItem, Item $quoteItem, $checkWithChildren = false): bool
    {
        return $this->getProcessor($quoteItem->getProductType())->isWarrantyRelatedToQuoteItem($warrantyItem, $quoteItem, $checkWithChildren);
    }

    /**
     * Return sku for warrantable product
     *
     * @param Item $quoteItem
     * @return string
     */
    public function getRelationQuoteItemSku($quoteItem): string
    {
        return $this->getProcessor($quoteItem->getProductType())
            ->getRelationQuoteItemSku($quoteItem);;
    }

    public function getOfferQuoteItemSku(CartItemInterface $quoteItem): string
    {
        return $this->getProcessor($quoteItem->getProductType())
            ->getOfferQuoteItemSku($quoteItem);
    }

    public function getRelatedQuoteItemByWarrantyData($warrantyData)
    {
        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (\Exception $e) {
            //log that quote is not loaded
            return null;
        }
        /** @var CartItemInterface $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            $relatedProcessor = $this->getProcessor($quoteItem->getProductType());
            if ($relatedProcessor->isWarrantyDataRelatedToQuoteItem($warrantyData, $quoteItem)) {
                return $quoteItem;
            }
        }
        return null;
    }

    public function quoteItemHasWarranty($quoteItem)
    {

    }

    public function getWarrantiesByQuoteItem($quoteItem)
    {
        $quote = $this->checkoutSession->getQuote();

        $warranties = [];
        foreach ($quote->getAllItems() as $item) {
            $relatedProcessor = $this->getProcessor($quoteItem->getProductType());

            if ($item->getProductType() !== Type::TYPE_CODE) {
                continue;
            }
            if ($relatedProcessor->isWarrantyRelatedToQuoteItem($item, $quoteItem)) {
                $warranties[] = $item;
            }
        }
        return $warranties;

    }

    /**
     * Get SKU processor by product type
     *
     * @param $productType
     * @return RelationProcessorInterface
     */
    private function getProcessor($productType)
    {
        $processorType = self::DEFAULT_SKU_PROCESSOR;

        if (isset($this->relationProcessors[$productType])) {
            $processorType = $productType;
        }
        return $this->relationProcessors[$processorType];
    }
}