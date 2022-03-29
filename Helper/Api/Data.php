<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

declare(strict_types=1);

namespace Extend\Warranty\Helper\Api;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\App\Cache\Type\Config;
use Extend\Warranty\Model\Config\Source\AuthMode;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
    /**
     * General settings
     */
    const WARRANTY_ENABLE_EXTEND_ENABLE_XML_PATH = 'warranty/enableExtend/enable';
    const WARRANTY_ENABLE_EXTEND_ENABLE_BALANCE_XML_PATH = 'warranty/enableExtend/enableBalance';
    const WARRANTY_ENABLE_EXTEND_LOGGING_ENABLED_XML_PATH = 'warranty/enableExtend/logging_enabled';

    /**
     * Authentication settings
     */
    const WARRANTY_AUTHENTICATION_AUTH_MODE_XML_PATH = 'warranty/authentication/auth_mode';
    const WARRANTY_AUTHENTICATION_STORE_ID_XML_PATH = 'warranty/authentication/store_id';
    const WARRANTY_AUTHENTICATION_API_KEY_XML_PATH = 'warranty/authentication/api_key';
    const WARRANTY_AUTHENTICATION_SANDBOX_STORE_ID_XML_PATH = 'warranty/authentication/sandbox_store_id';
    const WARRANTY_AUTHENTICATION_SANDBOX_API_KEY_XML_PATH = 'warranty/authentication/sandbox_api_key';
    const WARRANTY_AUTHENTICATION_API_URL_XML_PATH = 'warranty/authentication/api_url';
    const WARRANTY_AUTHENTICATION_SANDBOX_API_URL_XML_PATH = 'warranty/authentication/sandbox_api_url';
    const WARRANTY_AUTHENTICATION_STORE_NAME = 'warranty/authentication/store_name';

    /**
     * Contracts settings
     */
    const WARRANTY_CONTRACTS_ENABLED_XML_PATH = 'warranty/contracts/create';
    const WARRANTY_CONTRACTS_MODE_XML_PATH = 'warranty/contracts/mode';
    const WARRANTY_CONTRACTS_BATCH_SIZE_XML_PATH = 'warranty/contracts/batch_size';
    const WARRANTY_CONTRACTS_STORAGE_PERIOD_XML_PATH = 'warranty/contracts/storage_period';
    const WARRANTY_CONTRACTS_REFUND_ENABLED_XML_PATH = 'warranty/enableExtend/enableRefunds';
    const WARRANTY_CONTRACTS_AUTO_REFUND_ENABLED_XML_PATH = 'warranty/contracts/auto_refund_enabled';

    /**
     * Offers settings
     */
    const WARRANTY_OFFERS_SHOPPING_CART_ENABLED_XML_PATH = 'warranty/enableExtend/enableCartOffers';
    const WARRANTY_OFFERS_PDP_ENABLED_XML_PATH = 'warranty/offers/pdp_enabled';
    const WARRANTY_OFFERS_PRODUCTS_LIST_ENABLED_XML_PATH = 'warranty/offers/products_list_enabled';
    const WARRANTY_OFFERS_INTERSTITIAL_CART_ENABLED_XML_PATH = 'warranty/offers/interstitial_cart_enabled';
    const LEADS_MODAL_ENABLED_XML_PATH = 'warranty/offers/leads_modal_enabled';
    const ORDER_OFFERS_ENABLED_XML_PATH = 'warranty/offers/order_offers_enabled';

    /**
     * Products settings
     */
    const WARRANTY_PRODUCTS_BATCH_SIZE_XML_PATH = 'warranty/products/batch_size';
    const WARRANTY_PRODUCTS_LAST_SYNC_DATE_XML_PATH = 'warranty/products/lastSync';
    const WARRANTY_PRODUCTS_CRON_SYNC_ENABLED_XML_PATH = 'warranty/products/cron_sync_enabled';

    /**
     * Leads settings
     */
    const WARRANTY_ENABLE_EXTEND_ENABLE_LEADS_XML_PATH = 'warranty/enableExtend/enableLeads';

    /**
     * Lead token url param
     */
    const LEAD_TOKEN_URL_PARAM = 'leadToken';

    /**
     * Module name
     */
    const MODULE_NAME = 'Extend_Warranty';

    /**
     * Module List Interface
     *
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * Config Resource
     *
     * @var ConfigResource
     */
    private $configResource;

    /**
     * Cache Manager
     *
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * Data constructor
     *
     * @param Context $context
     * @param ModuleListInterface $moduleList
     * @param ConfigResource $configResource
     * @param CacheManager $cacheManager
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        ConfigResource $configResource,
        CacheManager $cacheManager
    ) {
        $this->moduleList = $moduleList;
        $this->configResource = $configResource;
        $this->cacheManager = $cacheManager;
        parent::__construct($context);
    }

    /**
     * Get module version
     *
     * @return string
     */
    public function getModuleVersion(): string
    {
        $module = $this->moduleList->getOne(self::MODULE_NAME);

        return $module['setup_version'] ?? '';
    }

    /**
     * Check if enabled
     *
     * @param string $scopeType
     * @param string|int|null $scopeId
     * @return bool
     */
    public function isExtendEnabled(
        string $scopeType = ScopeInterface::SCOPE_STORES,
        $scopeId = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::WARRANTY_ENABLE_EXTEND_ENABLE_XML_PATH,
            $scopeType,
            $scopeId
        );
    }

    /**
     * Check if Extend in live auth mode
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return bool
     */
    public function isExtendLive(
        string $scopeType = ScopeInterface::SCOPE_STORES,
        $scopeId = null
    ): bool {
        $authMode = (int)$this->scopeConfig->getValue(
            self::WARRANTY_AUTHENTICATION_AUTH_MODE_XML_PATH,
            $scopeType,
            $scopeId
        );

        return $authMode === AuthMode::LIVE_VALUE;
    }

    /**
     * Get store ID
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return string
     */
    public function getStoreId(
        string $scopeType = ScopeInterface::SCOPE_STORES,
        $scopeId = null
    ): string {
        $path = $this->isExtendLive($scopeType, $scopeId) ? self::WARRANTY_AUTHENTICATION_STORE_ID_XML_PATH
            : self::WARRANTY_AUTHENTICATION_SANDBOX_STORE_ID_XML_PATH;

        return (string)$this->scopeConfig->getValue($path, $scopeType, $scopeId);
    }

    /**
     * Get API key
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return string
     */
    public function getApiKey(
        string $scopeType = ScopeInterface::SCOPE_STORES,
        $scopeId = null
    ): string {
        $path = $this->isExtendLive($scopeType, $scopeId) ? self::WARRANTY_AUTHENTICATION_API_KEY_XML_PATH
            : self::WARRANTY_AUTHENTICATION_SANDBOX_API_KEY_XML_PATH;

        return (string)$this->scopeConfig->getValue($path, $scopeType, $scopeId);
    }

    /**
     * Get API url
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return string
     */
    public function getApiUrl(
        string $scopeType = ScopeInterface::SCOPE_STORES,
        $scopeId = null
    ): string {
       $path = $this->isExtendLive($scopeType, $scopeId) ? self::WARRANTY_AUTHENTICATION_API_URL_XML_PATH
            : self::WARRANTY_AUTHENTICATION_SANDBOX_API_URL_XML_PATH;

        return (string)$this->scopeConfig->getValue($path);
    }

    /**
     * Check if cart balance enabled
     *
     * @param string|int|null $storeId
     * @return bool
     */
    public function isBalancedCart($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::WARRANTY_ENABLE_EXTEND_ENABLE_BALANCE_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Check if logging enabled
     *
     * @return bool
     */
    public function isLoggingEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::WARRANTY_ENABLE_EXTEND_LOGGING_ENABLED_XML_PATH);
    }

    /**
     * Check if warranty contract creation for order item is enabled
     *
     * @param string|int|null $storeId
     * @return bool
     */
    public function isWarrantyContractEnabled($storeId = null): bool
    {
        if ($this->scopeConfig->getValue(
            self::WARRANTY_CONTRACTS_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId) > 0
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get contract crete API
     *
     * @param string $scopeType
     * @param $storeId
     * @return int
     */
    public function getContractCreateApi(
        string $scopeType = ScopeInterface::SCOPE_STORES,
        $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
                self::WARRANTY_CONTRACTS_ENABLED_XML_PATH,
                $scopeType,
                $storeId);
    }

    /**
     * Get contract creating mode
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return bool
     */
    public function isContractCreateModeScheduled(
        string $scopeType = ScopeInterface::SCOPE_STORES,
               $scopeId = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::WARRANTY_CONTRACTS_MODE_XML_PATH,
            $scopeType,
            $scopeId
        );
    }

    /**
     * Check if refund enabled
     *
     * @param string|int|null $storeId
     * @return bool
     */
    public function isRefundEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::WARRANTY_CONTRACTS_REFUND_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Check if a refund should be created automatically when credit memo is created
     *
     * @param string|int|null $storeId
     * @return bool
     */
    public function isAutoRefundEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::WARRANTY_CONTRACTS_AUTO_REFUND_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get contracts batch size
     *
     * @param string|int|null $storeId
     * @return int
     */
    public function getContractsBatchSize($storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::WARRANTY_CONTRACTS_BATCH_SIZE_XML_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get storage period, days
     *
     * @param string|int|null $storeId
     * @return int
     */
    public function getStoragePeriod($storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::WARRANTY_CONTRACTS_STORAGE_PERIOD_XML_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if shopping cart offers enabled
     *
     * @param string|int|null $storeId
     * @return bool
     */
    public function isShoppingCartOffersEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::WARRANTY_OFFERS_SHOPPING_CART_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Check if product detail page offers enabled
     *
     * @param string|int|null $storeId
     * @return bool
     */
    public function isProductDetailPageOffersEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::WARRANTY_OFFERS_PDP_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Check if products list offers enabled
     *
     * @param string|int|null $storeId
     * @return bool
     */
    public function isProductsListOffersEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::WARRANTY_OFFERS_PRODUCTS_LIST_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Check if interstitial cart offers enabled
     *
     * @param string|int|null $storeId
     * @return bool
     */
    public function isInterstitialCartOffersEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::WARRANTY_OFFERS_INTERSTITIAL_CART_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Check if Post Purchase Leads Modal enabled
     *
     * @param string|int|null $storeId
     * @return bool
     */
    public function isLeadsModalEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::LEADS_MODAL_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Check if Order Information Offers enabled
     *
     * @param string|int|null $storeId
     * @return bool
     */
    public function isOrderOffersEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::ORDER_OFFERS_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Get products batch size
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return int
     */
    public function getProductsBatchSize(
        string $scopeType = ScopeInterface::SCOPE_STORES,
        $scopeId = null
    ): int {
        return (int)$this->scopeConfig->getValue(
            self::WARRANTY_PRODUCTS_BATCH_SIZE_XML_PATH,
            $scopeType,
            $scopeId
        );
    }

    /**
     * Set last product sync date
     *
     * @param string $value
     * @param string $scopeType
     * @param int|string|null $scopeId
     */
    public function setLastProductSyncDate(
        string $value,
        string $scopeType = ScopeInterface::SCOPE_STORES,
        $scopeId = null
    ) {
        $this->configResource->saveConfig(
            self::WARRANTY_PRODUCTS_LAST_SYNC_DATE_XML_PATH,
            $value,
            $scopeType,
            (int)$scopeId
        );
        $this->cacheManager->clean([Config::TYPE_IDENTIFIER]);
    }

    /**
     * Get last product sync date
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return string
     */
    public function getLastProductSyncDate(
        string $scopeType = ScopeInterface::SCOPE_STORES,
        $scopeId = null
    ): string {
        return (string)$this->scopeConfig->getValue(
            self::WARRANTY_PRODUCTS_LAST_SYNC_DATE_XML_PATH,
            $scopeType,
            $scopeId
        );
    }

    /**
     * Check if product synchronization by cron is enabled
     */
    public function isProductSyncByCronEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::WARRANTY_PRODUCTS_CRON_SYNC_ENABLED_XML_PATH);
    }

    /**
     * Check if leads enabled
     *
     * @param null $storeId
     * @return bool
     */
    public function isLeadEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::WARRANTY_ENABLE_EXTEND_ENABLE_LEADS_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Get store name
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return string
     */
    public function getStoreName(
        string $scopeType = ScopeInterface::SCOPE_STORES,
        $scopeId = null
    ): string {
        return (string)$this->scopeConfig->getValue(
            self::WARRANTY_AUTHENTICATION_STORE_NAME,
            $scopeType,
            $scopeId
        );
    }
}
