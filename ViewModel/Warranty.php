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
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderItemSearchResultInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Exception;

/**
 * Class Warranty
 *
 * Warranty ViewModel
 */
class Warranty implements ArgumentInterface
{
    /**
     * Data Helper Model
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Json Serializer Model
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
     * Warranty Tracking Helper
     *
     * @var TrackingHelper
     */
    private $trackingHelper;

    /**
     * Offer
     *
     * @var OfferModel
     */
    private $offerModel;

    /**
     * Checkout Session Model
     *
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * Request Model
     *
     * @var Http
     */
    private $request;

    /**
     * Order Item Repository Model
     *
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * Search Criteria Builder Model
     *
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    private $storeManager;

    private $adminSession;

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
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        DataHelper $dataHelper,
        JsonSerializer $jsonSerializer,
        LinkManagementInterface $linkManagement,
        TrackingHelper $trackingHelper,
        OfferModel $offerModel,
        CheckoutSession $checkoutSession,
        Http $request,
        OrderItemRepositoryInterface $orderItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager,
        AdminSession $adminSession
    ) {
        $this->dataHelper = $dataHelper;
        $this->jsonSerializer = $jsonSerializer;
        $this->linkManagement = $linkManagement;
        $this->trackingHelper = $trackingHelper;
        $this->offerModel = $offerModel;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->orderItemRepository = $orderItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
        $this->adminSession = $adminSession;
    }

    /**
     * Check if module enabled
     * @param null|int $storeId
     *
     * @return bool
     */
    public function isExtendEnabled(int $storeId = null): bool
    {
        $result = false;

        if ($storeId) {
            return  $this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId);
        }

        if ($this->isAdmin()) {
            $stores = $this->storeManager->getStores();
            foreach ($stores as $store) {
                $result = $this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $store->getId());
                if ($result) {
                    break;
                }
            }
        } else {
            $storeId = $this->storeManager->getStore()->getId();
            $result = $this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId);
        }

        return $result;
    }

    /**
     * Check if has warranty in cart
     *
     * @param CartInterface $quote
     * @param string $sku
     * @return bool
     */
    public function hasWarranty(CartInterface $quote, string $id): bool
    {
        $hasWarranty = false;

        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            if ($item->getProductType() === Type::TYPE_CODE) {
                $associatedProduct = $item->getOptionByCode(Type::RELATED_ITEM_ID);
                if ($associatedProduct && $associatedProduct->getValue() === $id) {
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
        $result = false;
        if ($this->isAdmin()) {
            $stores = $this->storeManager->getStores();
            foreach ($stores as $store) {
                $result = $this->dataHelper->isShoppingCartOffersEnabled($store->getId());
                if ($result) {
                    break;
                }
            }
        } else {
            $storeId = $this->storeManager->getStore()->getId();
            $result = $this->dataHelper->isShoppingCartOffersEnabled($storeId);
        }

        return $result;
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
     * Check if offers are enabled on individual bundle product items
     *
     * @return bool
     */
    public function isIndividualBundleItemOffersEnabled(): bool
    {
        return $this->dataHelper->isIndividualBundleItemOffersEnabled();
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
        $result = false;
        if ($this->isAdmin()) {
            $stores = $this->storeManager->getStores();
            foreach ($stores as $store) {
                $result = $this->dataHelper->isLeadEnabled($store->getId());
                if ($result) {
                    break;
                }
            }
        } else {
            $storeId = $this->storeManager->getStore()->getId();
            $result = $this->dataHelper->isLeadEnabled($storeId);
        }

        return $result;
    }

    /**
     * Check does quote have warranty item for the item
     *
     * @param string $sku
     * @return bool
     */
    public function isWarrantyInQuote(string $sku): bool
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
     * Check does later orders have warranty item for the item
     *
     * @param Item $item
     * @return bool
     */
    public function isWarrantyInLaterOrders(Item $item): bool
    {
        $isWarrantyInLaterOrders = false;
        $leadToken = $item->getLeadToken();
        $createdAt = $item->getCreatedAt();

        if (!empty($leadToken)) {
            $orderItems = $this->getOrderItemsByLeadToken($leadToken, $createdAt);

            if (count($orderItems) > 0) {
                $isWarrantyInLaterOrders = true;
            }
        }

        return $isWarrantyInLaterOrders;
    }

    /**
     * Get order items created later than the current by lead token
     *
     * @param string $leadToken
     * @param string $createdAt
     *
     * @return OrderItemSearchResultInterface
     */
    private function getOrderItemsByLeadToken(string $leadToken, string $createdAt)
    {
        $this->searchCriteriaBuilder->addFilter(
            'lead_token',
            $leadToken,
            'eq'
        );
        $this->searchCriteriaBuilder->addFilter(
            'created_at',
            $createdAt,
            'gt'
        );
        $searchCriteria = $this->searchCriteriaBuilder->create();

        return $this->orderItemRepository->getList($searchCriteria);
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

    /**
     * Get Lead Token
     *
     * @param Item $item
     * @return string
     */
    public function getLeadToken(Item $item)
    {
        $leadToken = $item->getLeadToken() ?? '';

        if (!empty($leadToken)) {
            try {
                $leadToken = implode(", ", $this->unserialize($leadToken));
            } catch (Exception $exception) {
                $leadToken = '';
            }
        }

        return $leadToken;
    }

    public function getBundleProductsJsonFromOptions(array $options) {
        $productsJson = [];

        foreach ($options as $option) {
            /* @var \Magento\Bundle\Model\Option $option */
            foreach ($option->getSelections() as $selection) {
                $productsJson[$option->getId()][$selection->getSelectionId()] = [
                    'id' => $selection->getId(),
                    'sku' => $selection->getSku()
                ];
            }
        }

        return $productsJson;
    }

    /**
     * @return bool
     */
    private function isAdmin()
    {
        return (bool)$this->adminSession->getUser();
    }
}
