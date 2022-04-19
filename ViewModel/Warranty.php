<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\ViewModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\ConfigurableProduct\Api\LinkManagementInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Product\Type;
use Magento\Quote\Api\Data\CartInterface;
use Extend\Warranty\Helper\Tracking as TrackingHelper;
use Extend\Warranty\Model\Offers as OfferModel;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Request\Http;
use Exception;

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
     * Tracking Helper
     *
     * @var TrackingHelper
     */
    private $trackingHelper;

    /**
     * Offer Model
     *
     * @var OfferModel
     */
    private $offerModel;

    /**
     * Checkout Session
     *
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * Request
     *
     * @var Http
     */
    private $request;

    /**
     * Warranty constructor
     *
     * @param DataHelper $dataHelper
     * @param JsonSerializer $jsonSerializer
     * @param LinkManagementInterface $linkManagement
     * @param TrackingHelper $trackingHelper
     * @param OfferModel $offerModel
     * @param CheckoutSession $checkoutSession
     * @param Http $request
     */
    public function __construct(
        DataHelper $dataHelper,
        JsonSerializer $jsonSerializer,
        LinkManagementInterface $linkManagement,
        TrackingHelper $trackingHelper,
        OfferModel $offerModel,
        CheckoutSession $checkoutSession,
        Http $request
    ) {
        $this->dataHelper = $dataHelper;
        $this->jsonSerializer = $jsonSerializer;
        $this->linkManagement = $linkManagement;
        $this->trackingHelper = $trackingHelper;
        $this->offerModel = $offerModel;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
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
     * Check if products list offers enabled
     *
     * @return bool
     */
    public function isProductsListOffersEnabled(): bool
    {
        return $this->dataHelper->isProductsListOffersEnabled();
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
                $isProductHasOffers[$itemSku] = $this->offerModel->hasOffers($itemSku);
            }
        } else {
            $isProductHasOffers[$productSku] = $this->offerModel->hasOffers($productSku);
        }

        return $this->jsonSerializer->serialize($isProductHasOffers);
    }

    /**
     * Check if tracking enabled
     *
     * @return bool
     */
    public function isTrackingEnabled(): bool
    {
        return $this->trackingHelper->isTrackingEnabled();
    }

    /**
     * Check is leads enabled
     *
     * @return bool
     */
    public function isLeadEnabled(): bool
    {
        return $this->dataHelper->isLeadEnabled();
    }

    /**
     * Check does quote have warranty item for the item
     *
     * @param string $sku
     * @return bool
     */
    public function isWarrantyInQuoteForSku(string $sku): bool
    {
        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (LocalizedException $exception) {
            $quote = null;
        }

        if ($quote) {
            $hasWarranty = $this->hasWarranty($quote, $sku);
        }

        return $hasWarranty ?? false;
    }

    /**
     * Check is post purchase lead modal enabled
     *
     * @return bool
     */
    public function isPostPurchaseLeadModalEnabled(): bool
    {
        return $this->dataHelper->isLeadsModalEnabled();
    }

    /**
     * Check is warranty information order offers enabled
     *
     * @return bool
     */
    public function isOrderOffersEnabled(): bool
    {
        return $this->dataHelper->isOrderOffersEnabled();
    }

    /**
     * Get Lead Token From Url
     *
     * @return string
     */
    public function getLeadTokenFromUrl(): string
    {
        return $this->request->getParam(DataHelper::LEAD_TOKEN_URL_PARAM) ?? '';
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
}

