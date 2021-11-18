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

namespace Extend\Warranty\ViewModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\ConfigurableProduct\Api\LinkManagementInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Product\Type;
use Magento\Quote\Api\Data\CartInterface;
use Extend\Warranty\Helper\Api as ApiHelper;
use InvalidArgumentException;

/**
 * Class Warranty
 */
class Warranty implements ArgumentInterface
{
    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Api Helper
     *
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * Json Serializer
     *
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * Link Management Interface
     *
     * @var LinkManagementInterface
     */
    private $linkManagement;

    /**
     * Warranty constructor
     *
     * @param DataHelper $dataHelper
     * @param ApiHelper $apiHelper
     * @param JsonSerializer $jsonSerializer
     * @param LinkManagementInterface $linkManagement
     */
    public function __construct(
        DataHelper $dataHelper,
        ApiHelper $apiHelper,
        JsonSerializer $jsonSerializer,
        LinkManagementInterface $linkManagement
    ) {
        $this->dataHelper = $dataHelper;
        $this->apiHelper = $apiHelper;
        $this->jsonSerializer = $jsonSerializer;
        $this->linkManagement = $linkManagement;
    }

    /**
     * Check if module enabled
     *
     * @return bool
     */
    public function isExtendEnabled(): bool
    {
        return $this->dataHelper->isExtendEnabled();
    }

    /**
     * Check if has warranty in cart
     *
     * @param CartInterface $quote
     * @param string $sku
     * @return bool
     */
    public function hasWarranty(CartInterface $quote, string $sku): bool
    {
        $hasWarranty = false;

        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            if ($item->getProductType() === Type::TYPE_CODE) {
                $associatedProduct = $item->getOptionByCode('associated_product');
                if ($associatedProduct && $associatedProduct->getValue() === $sku) {
                    $hasWarranty = true;
                }
            }
        }

        return $hasWarranty;
    }

    /**
     * Check if shopping cart offers enabled
     *
     * @return bool
     */
    public function isShoppingCartOffersEnabled(): bool
    {
        return $this->dataHelper->isShoppingCartOffersEnabled();
    }

    /**
     * Check if product detail page offers enabled
     *
     * @return bool
     */
    public function isProductDetailPageOffersEnabled(): bool
    {
        return $this->dataHelper->isProductDetailPageOffersEnabled();
    }

    /**
     * Check if interstitial cart offers enabled
     *
     * @return bool
     */
    public function isInterstitialCartOffersEnabled(): bool
    {
        return $this->dataHelper->isInterstitialCartOffersEnabled();
    }

    /**
     * Check if product has warranty offers
     *
     * @param ProductInterface $product
     * @return string
     * @thrown InvalidArgumentException
     */
    public function isProductHasOffers(ProductInterface $product): string
    {
        $isProductHasOffers = [];
        $productSku = $product->getSku();

        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $items = $this->linkManagement->getChildren($productSku);
            foreach ($items as $item) {
                $itemSku = $item->getSku();
                $isProductHasOffers[$itemSku] = $this->apiHelper->isProductHasOffers($itemSku);
            }
        } else {
            $isProductHasOffers[$productSku] = $this->apiHelper->isProductHasOffers($productSku);
        }

        return $this->jsonSerializer->serialize($isProductHasOffers);
    }
}