<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\ViewModel;

use Extend\Warranty\Model\Config\Source\AuthMode;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Helper\Tracking as TrackingHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Backend\Model\Auth\Session as AdminSession;
use InvalidArgumentException;

/**
 * Class Installation
 *
 * Warranty Installation ViewModel
 */
class Installation implements ArgumentInterface
{
    /**
     * Warranty Api DataHelper
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
     * Json Serializer Model
     *
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AdminSession
     */
    private $adminSession;

    /**
     * @var ProductMetadata
     */
    private $productMetadata;

    protected $scopeConfig;

    /**
     * Installation constructor
     *
     * @param DataHelper $dataHelper
     * @param TrackingHelper $trackingHelper
     * @param JsonSerializer $jsonSerializer
     * @param StoreManagerInterface $storeManager
     * @param AdminSession $adminSession
     * @param ProductMetadata $productMetadata
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        DataHelper            $dataHelper,
        TrackingHelper        $trackingHelper,
        JsonSerializer        $jsonSerializer,
        StoreManagerInterface $storeManager,
        AdminSession          $adminSession,
        ProductMetadata       $productMetadata,
        ScopeConfigInterface  $scopeConfig
    ) {
        $this->dataHelper = $dataHelper;
        $this->trackingHelper = $trackingHelper;
        $this->jsonSerializer = $jsonSerializer;
        $this->storeManager = $storeManager;
        $this->adminSession = $adminSession;
        $this->productMetadata = $productMetadata;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check if module enabled
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isExtendEnabled($storeId = null): bool
    {
        $result = false;
        if ($this->isAdmin()) {
            $stores = $this->storeManager->getStores();
            foreach ($stores as $store) {
                $result = $this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $store->getId());
                if ($result) {
                    break;
                }
            }
        } else {
            $storeId = $storeId ?? $this->storeManager->getStore()->getId();
            $result = $this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId);
        }

        return $result;
    }

    /**
     * Get JSON config
     *
     * @return string
     */
    public function getJsonConfig($storeId = null): string
    {
        $jsonConfig = '';

        $extendStoreId = $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId);
        if ($extendStoreId) {
            $config = [
                'storeId' => $extendStoreId,
                'environment' => $this->dataHelper
                    ->isExtendLive(
                        ScopeInterface::SCOPE_STORES,
                        $storeId)
                    ? AuthMode::LIVE
                    : AuthMode::DEMO,
            ];

            if ($storeCountry = $this->getRegion($storeId)) {
                $config['region'] = $storeCountry;
            }

            if ($storeLocale = $this->getLocale($storeId)) {
                $config['locale'] = $storeLocale;
            }

            try {
                $jsonConfig = $this->jsonSerializer->serialize($config);
            } catch (InvalidArgumentException $exception) {
                $jsonConfig = '';
            }
        }

        return $jsonConfig;
    }

    /**
     * Get 'Extend.integration' JSON config
     *
     * @return string
     */
    public function getIntegrationJsonConfig($storeId = null): string
    {
        $storeId = $storeId ?? $this->storeManager->getStore()->getId();
        $isLiveMode = $this->dataHelper->isExtendLive(ScopeInterface::SCOPE_STORES, $storeId);

        $config = [
            'general' => [
                'enableExtend' => $this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId),
                'balancedCart' => $this->dataHelper->isBalancedCart($storeId),
                'enableLeads' => $this->dataHelper->isLeadEnabled($storeId),
                'enableLogging' => $this->dataHelper->isLoggingEnabled()
            ],
            'auth' => [
                'mode' => $isLiveMode ? AuthMode::LIVE : AuthMode::DEMO,
                'id' => $isLiveMode ? $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId) : null,
                'sandboxId' => $isLiveMode ? null : $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId),
                'extendStoreName' => $this->dataHelper->getStoreName(ScopeInterface::SCOPE_STORES, $storeId)
            ],
            'contracts' => [
                'createWarrantyContract' => $this->dataHelper->isWarrantyContractEnabled($storeId),
                'contractEvent' => $this->dataHelper->getContractCreateEvent(ScopeInterface::SCOPE_STORES, $storeId),
                'contractCreatingMode' => $this->dataHelper->isContractCreateModeScheduled(ScopeInterface::SCOPE_STORES, $storeId) ? __('scheduled') : __('event-based'),
                'cronContractSettings' => [
                    'frequency' => $this->dataHelper->getContractFrequency($storeId),
                    'batchSize' => $this->dataHelper->getContractsBatchSize($storeId),
                    'storagePeriod' => $this->dataHelper->getStoragePeriod($storeId)
                ],
                'enableRefunds' => $this->dataHelper->isRefundEnabled($storeId),
                'createRefundAutomatically' => $this->dataHelper->isAutoRefundEnabled($storeId)
            ],
            'offers' => [
                'displayCartOffers' => $this->dataHelper->isShoppingCartOffersEnabled($storeId),
                'enablePDPOffers' => $this->dataHelper->isProductDetailPageOffersEnabled($storeId),
                'enableProductsListOffers' => $this->dataHelper->isProductsListOffersEnabled($storeId),
                'enableInterstitialCartOffers' => $this->dataHelper->isInterstitialCartOffersEnabled($storeId),
                'enablePostPurchaseModal' => $this->dataHelper->isLeadsModalEnabled($storeId),
                'enableOrderInformationOffers' => $this->dataHelper->isOrderOffersEnabled($storeId),
                'displaySettings' => [
                    'pdpPlacement' => $this->dataHelper->getProductDetailPageOffersPlacement(ScopeInterface::SCOPE_STORES, $storeId)
                ]
                //'displayOffersOnIndividualBundleItems' => false
            ],
            'syncProducts' => [
                'batchSize' => $this->dataHelper->getProductsBatchSize(ScopeInterface::SCOPE_STORES, $storeId),
                'lastSyncDate' => $this->dataHelper->getLastProductSyncDate(ScopeInterface::SCOPE_STORES, $storeId),
                'enableSpecialPrice' => $this->dataHelper->isProductSpecialPriceSyncEnabled(ScopeInterface::SCOPE_STORES, $storeId),
                'enableCronSync' => $this->dataHelper->isProductSyncByCronEnabled()
            ],
            'syncHistoricalOrders' => [
                'batchSize' => $this->dataHelper->getHistoricalOrdersBatchSize(ScopeInterface::SCOPE_STORES, $storeId),
                'lastSendDate' => $this->dataHelper->getHistoricalOrdersSyncPeriod(ScopeInterface::SCOPE_STORES, $storeId),
                'enableCronSync' => $this->dataHelper->isHistoricalOrdersCronSyncEnabled(ScopeInterface::SCOPE_STORES, $storeId)
            ],

            'trackingEnabled' => $this->trackingHelper->isTrackingEnabled($storeId),
            'versions' => [
                'magentoVersion' => $this->productMetadata->getVersion(),
                'moduleVersion' => $this->dataHelper->getModuleVersion()
            ]
        ];

        try {
            $jsonConfig = $this->jsonSerializer->serialize($config);
        } catch (InvalidArgumentException $exception) {
            $jsonConfig = '';
        }

        return $jsonConfig;
    }

    /**
     * Get JS mode
     *
     * @return string
     */
    public function getJsMode(): string
    {
        return "https://sdk.helloextend.com/extend-sdk-client/v1/extend-sdk-client.min.js";
    }

    /**
     * @return bool
     */
    private function isAdmin()
    {
        return (bool)$this->adminSession->getUser();
    }

    /**
     *
     * Return Store Country
     *
     * @return string
     */
    private function getRegion($storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            \Magento\Config\Model\Config\Backend\Admin\Custom::XML_PATH_GENERAL_COUNTRY_DEFAULT,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Return Store Locale
     *
     * @return string
     */
    private function getLocale($storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            \Magento\Config\Model\Config\Backend\Admin\Custom::XML_PATH_GENERAL_LOCALE_CODE,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }


}
