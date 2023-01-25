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

use Exception;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Helper\Data as WarrantyHelper;
use Extend\Warranty\Helper\Tracking as TrackingHelper;
use Extend\Warranty\Model\Api\Sync\Lead\LeadInfoRequest;
use Extend\Warranty\Model\Offers as OfferModel;
use Extend\Warranty\Model\Product\Type;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\ConfigurableProduct\Api\LinkManagementInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderItemSearchResultInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

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

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AdminSession
     */
    private $adminSession;

    /**
     * @var LeadInfoRequest
     */
    private $leadInfoRequest;

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
     * @param StoreManagerInterface $storeManager
     * @param AdminSession $adminSession
     * @param LeadInfoRequest $leadInfoRequest
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
        AdminSession $adminSession,
        LeadInfoRequest $leadInfoRequest
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
        $this->leadInfoRequest = $leadInfoRequest;
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
     * Check if has warranty in cart by itemId
     *
     * @param CartInterface $quote
     * @param int $id
     * @return bool
     */
    public function hasWarranty(CartInterface $quote, int $id): bool
    {
        $hasWarranty = false;
        $checkQuoteItem = $quote->getItemById($id);
        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            if(
                $item->getProductType() === Type::TYPE_CODE
                && $checkQuoteItem
                && WarrantyHelper::isWarrantyRelatedToQuoteItem($item,$checkQuoteItem)){
                $hasWarranty = true;
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
                $result = $this->dataHelper->isShoppingAdminCartOffersEnabled($store->getId());
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
     * @param int $id
     * @return bool
     */
    public function isWarrantyInQuote(int $id): bool
    {
        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (LocalizedException $exception) {
            $quote = null;
        }

        if ($quote) {
            $hasWarranty = $this->hasWarranty($quote, $id);
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

    /**
     * @return bool
     */
    private function isAdmin()
    {
        return (bool)$this->adminSession->getUser();
    }

    /**
     * @param string $leadToken
     * @return bool
     */
    public function isExpired(string $leadToken): bool
    {
        $apiUrl = $this->dataHelper->getApiUrl();
        $apiStoreId = $this->dataHelper->getStoreId();
        $apiKey = $this->dataHelper->getApiKey();

        $this->leadInfoRequest->setConfig($apiUrl, $apiStoreId, $apiKey);
        $leadExpirationDate = $this->leadInfoRequest->create($leadToken)/1000;
        return $leadExpirationDate !== null && time() >= $leadExpirationDate;
    }
}
