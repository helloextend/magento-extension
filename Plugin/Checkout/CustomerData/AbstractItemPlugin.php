<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Plugin\Checkout\CustomerData;

use Extend\Warranty\Model\WarrantyRelation;
use Extend\Warranty\ViewModel\Warranty;
use Magento\Framework\UrlInterface;
use Magento\Checkout\CustomerData\AbstractItem;
use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Helper\Tracking as TrackingHelper;
use Magento\Quote\Model\Quote\Item;

/**
 * Class AbstractItemPlugin
 *
 * AbstractItemPlugin plugin
 */
class AbstractItemPlugin
{
    /**
     * Url builder Model
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Warranty Api Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Warranty Tracking Helper
     *
     * @var TrackingHelper
     */
    private $trackingHelper;

    /**
     * @var Warranty
     */
    private $warrantyViewModel;

    private $warrantyRelation;

    /**
     * AbstractItemPlugin constructor
     *
     * @param UrlInterface $urlBuilder
     * @param DataHelper $dataHelper
     * @param TrackingHelper $trackingHelper
     * @param Warranty $warrantyViewModel
     * @param WarrantyRelation $warrantyRelation
     */
    public function __construct(
        UrlInterface $urlBuilder,
        DataHelper $dataHelper,
        TrackingHelper $trackingHelper,
        Warranty $warrantyViewModel,
        WarrantyRelation $warrantyRelation
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->dataHelper = $dataHelper;
        $this->trackingHelper = $trackingHelper;
        $this->warrantyViewModel = $warrantyViewModel;
        $this->warrantyRelation = $warrantyRelation;
    }

    /**
     * Set 'isWarranty' flag for product image. Set data for render add warranty button on minicart
     *
     * @param AbstractItem $subject
     * @param array $result
     * @param Item $item
     * @return array
     */
    public function afterGetItemData(AbstractItem $subject, array $result, Item $item): array
    {
        $result['product_image']['isWarranty'] = isset($result['product_type'])
            && $result['product_type'] === Type::TYPE_CODE;


        if ($this->isShoppingCartOffersEnabled()
            && !$result['product_image']['isWarranty']
            && !$this->hasWarranty($item)
        ) {
            $result['product_can_add_warranty'] = true;
            $result['warranty_add_url'] = $this->getWarrantyAddUrl();
            $result['product_parent_id'] = $this->getParentId($item);
            $result['product_is_tracking_enabled'] = $this->isTrackingEnabled();
            $result['item_product_sku'] = $this->warrantyViewModel->getProductSkuByQuoteItem($item);
            $result['relation_sku'] = $this->warrantyViewModel->getRelationSkuByQuoteItem($item);
        } else {
            $result['product_can_add_warranty'] = false;
        }

        return $result;
    }

    /**
     * Check if has warranty in cart by quote Item
     *
     * @param Item $item
     * @return bool
     */
    private function hasWarranty(Item $checkQuoteItem): bool
    {
        return $this->warrantyRelation->quoteItemHasWarranty($checkQuoteItem);
    }

    /**
     * Get Warranty Cart Add Url
     *
     * @return string
     */
    private function getWarrantyAddUrl(): string
    {
        return $this->urlBuilder->getUrl('warranty/cart/add');
    }

    /**
     * Get Parent Product Id
     *
     * @param Item $item
     * @return string
     */
    private function getParentId(Item $item): string
    {
        return $item->getOptionByCode('simple_product') ? $item->getProductId() : '';
    }

    /**
     * Check if shopping cart offers enabled
     *
     * @return bool
     */
    private function isShoppingCartOffersEnabled(): bool
    {
        return $this->dataHelper->isShoppingCartOffersEnabled();
    }

    /**
     * Check if tracking enabled
     *
     * @return bool
     */
    private function isTrackingEnabled(): bool
    {
        return $this->trackingHelper->isTrackingEnabled();
    }
}
