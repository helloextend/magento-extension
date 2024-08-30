<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Helper\Api;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\App\Cache\Type\Config;
use Extend\Warranty\Model\Config\Source\AuthMode;
use Extend\Warranty\Api\ConnectorInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class Data
 *
 * Warranty Api Helper
 */
class Data extends AbstractHelper
{
    /**
     * General settings
     */
    public const WARRANTY_ENABLE_EXTEND_ENABLE_XML_PATH = 'warranty/enableExtend/enable';
    public const WARRANTY_VERSION_TAG_EXTEND_ENABLE_XML_PATH = 'warranty/version/tag';
    public const WARRANTY_ENABLE_EXTEND_ENABLE_BALANCE_XML_PATH = 'warranty/enableExtend/enableBalance';
    public const WARRANTY_ENABLE_EXTEND_LOGGING_ENABLED_XML_PATH = 'warranty/enableExtend/logging_enabled';

    /**
     * Authentication settings
     */
    public const WARRANTY_AUTHENTICATION_AUTH_MODE_XML_PATH = 'warranty/authentication/auth_mode';
    public const WARRANTY_AUTHENTICATION_STORE_ID_XML_PATH = 'warranty/authentication/store_id';
    public const WARRANTY_AUTHENTICATION_API_KEY_XML_PATH = 'warranty/authentication/api_key';
    public const WARRANTY_AUTHENTICATION_SANDBOX_STORE_ID_XML_PATH = 'warranty/authentication/sandbox_store_id';
    public const WARRANTY_AUTHENTICATION_SANDBOX_API_KEY_XML_PATH = 'warranty/authentication/sandbox_api_key';
    public const WARRANTY_AUTHENTICATION_API_URL_XML_PATH = 'warranty/authentication/api_url';
    public const WARRANTY_AUTHENTICATION_SANDBOX_API_URL_XML_PATH = 'warranty/authentication/sandbox_api_url';
    public const WARRANTY_AUTHENTICATION_STORE_NAME = 'warranty/authentication/store_name';
    public const WARRANTY_AUTHENTICATION_CLIENT_ID_XML_PATH = 'warranty/authentication/client_id';
    public const WARRANTY_AUTHENTICATION_SANDBOX_CLIENT_ID_XML_PATH = 'warranty/authentication/sandbox_client_id';
    public const WARRANTY_AUTHENTICATION_CLIENT_SECRET_XML_PATH = 'warranty/authentication/client_secret';
    public const WARRANTY_AUTHENTICATION_SANDBOX_CLIENT_SECRET_XML_PATH = 'warranty/authentication/sandbox_client_secret';
    public const WARRANTY_AUTHENTICATION_OAUTH_TOKEN_DATE_XML_PATH = 'warranty/authentication/oauth_token_date';
    public const WARRANTY_AUTHENTICATION_SANDBOX_OAUTH_TOKEN_DATE_XML_PATH = 'warranty/authentication/sandbox_oauth_token_date';

    /**
     * Contracts settings
     */
    public const WARRANTY_CONTRACTS_ENABLED_XML_PATH = 'warranty/contracts/create';
    public const WARRANTY_CONTRACTS_EVENT_XML_PATH = 'warranty/contracts/event';
    public const WARRANTY_CONTRACTS_MODE_XML_PATH = 'warranty/contracts/mode';
    public const WARRANTY_CONTRACTS_BATCH_SIZE_XML_PATH = 'warranty/contracts/batch_size';
    public const WARRANTY_CONTRACTS_FREQUENCY_XML_PATH = 'warranty/contracts/cron/frequency';
    public const WARRANTY_CONTRACTS_STORAGE_PERIOD_XML_PATH = 'warranty/contracts/storage_period';
    public const WARRANTY_CONTRACTS_REFUND_ENABLED_XML_PATH = 'warranty/enableExtend/enableRefunds';
    public const WARRANTY_CONTRACTS_AUTO_REFUND_ENABLED_XML_PATH = 'warranty/contracts/auto_refund_enabled';

    /**
     * Offers settings
     */

    public const WARRANTY_OFFERS_ADMIN_ENABLED_XML_PATH = 'warranty/enableExtend/enableAdminOffers';
    public const WARRANTY_OFFERS_SHOPPING_CART_ENABLED_XML_PATH = 'warranty/enableExtend/enableCartOffers';
    public const WARRANTY_OFFERS_PDP_ENABLED_XML_PATH = 'warranty/offers/pdp_enabled';
    public const WARRANTY_OFFERS_PRODUCTS_LIST_ENABLED_XML_PATH = 'warranty/offers/products_list_enabled';
    public const WARRANTY_OFFERS_INTERSTITIAL_CART_ENABLED_XML_PATH = 'warranty/offers/interstitial_cart_enabled';
    public const LEADS_MODAL_ENABLED_XML_PATH = 'warranty/offers/leads_modal_enabled';
    public const ORDER_OFFERS_ENABLED_XML_PATH = 'warranty/offers/order_offers_enabled';

    /**
     * Offers Display settings
     */
    public const WARRANTY_OFFERS_PDP_PLACEMENT_XML_PATH = 'warranty/offers/pdp_placement';

    /**
     * Products settings
     */
    public const WARRANTY_PRODUCTS_BATCH_SIZE_XML_PATH = 'warranty/products/batch_size';
    public const WARRANTY_PRODUCTS_LAST_SYNC_DATE_XML_PATH = 'warranty/products/lastSync';
    public const WARRANTY_PRODUCTS_CRON_SYNC_ENABLED_XML_PATH = 'warranty/products/cron_sync_enabled';
    public const WARRANTY_PRODUCT_SYNC_SPECIAL_PRICES_XML_PATH = 'warranty/products/enable_special_prices';

    /**
     * Historical orders settings
     */
    const WARRANTY_HISTORICAL_ORDERS_BATCH_SIZE_XML_PATH = 'warranty/historical_orders/batch_size';
    const WARRANTY_HISTORICAL_ORDERS_SYNC_PERIOD_XML_PATH = 'warranty/historical_orders/historical_orders_sync';
    const WARRANTY_HISTORICAL_ORDERS_CRON_SYNC_ENABLED_XML_PATH = 'warranty/historical_orders/enable_cron';

    /**
     * Leads settings
     */
    public const WARRANTY_ENABLE_EXTEND_ENABLE_LEADS_XML_PATH = 'warranty/enableExtend/enableLeads';

    /**
     * Lead token url param
     */
    public const LEAD_TOKEN_URL_PARAM = 'leadToken';

    /**
     * Module name
     */
    public const MODULE_NAME = 'Extend_Warranty';

    /**
     * Module List Model
     *
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * Config Resource Model
     *
     * @var ConfigResource
     */
    private $configResource;

    /**
     * Cache Manager Model
     *
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * Connector Interface
     *
     * @var ConnectorInterface
     */
    protected $connector;

    /**
     * Json Serializer Model
     *
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    /**
     * Data constructor
     * @param JsonSerializer $jsonSerializer
     * @param Context $context
     * @param ModuleListInterface $moduleList
     * @param ConfigResource $configResource
     * @param CacheManager $cacheManager
     */
    public function __construct(
        JsonSerializer     $jsonSerializer,
        ConnectorInterface $connector,
        Context $context,
        ModuleListInterface $moduleList,
        ConfigResource $configResource,
        CacheManager $cacheManager

    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->connector = $connector;
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
    public function getModuleVersion()
    {
        $module = $this->moduleList->getOne(self::MODULE_NAME);

        return $module['setup_version'] ?? '';
    }

    /**
     * @return string
     */
    public function getVersionTag(){
        return (string) $this->scopeConfig->getValue(self::WARRANTY_VERSION_TAG_EXTEND_ENABLE_XML_PATH);
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
    ) {
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
    ) {
        $authMode = (int)$this->scopeConfig->getValue(
            self::WARRANTY_AUTHENTICATION_AUTH_MODE_XML_PATH,
            $scopeType,
            $scopeId
        );

        return $authMode === AuthMode::LIVE_VALUE;
    }

    /**
     * Check if Extend in Oauth mode
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return bool
     */
    public function isExtendOauthEnabled(
        string $scopeType = ScopeInterface::SCOPE_STORES,
               $scopeId = null
    ) {
        if ($this->isExtendLive($scopeType, $scopeId)){
            //live
            $clientId = (string)$this->scopeConfig->getValue(
                self::WARRANTY_AUTHENTICATION_CLIENT_ID_XML_PATH,
                $scopeType,
                $scopeId
            );
            $clientSecret = (string)$this->scopeConfig->getValue(
                self::WARRANTY_AUTHENTICATION_CLIENT_SECRET_XML_PATH,
                $scopeType,
                $scopeId
            );
        }else{
            //sandbox
            $clientId = (string)$this->scopeConfig->getValue(
                self::WARRANTY_AUTHENTICATION_SANDBOX_CLIENT_ID_XML_PATH,
                $scopeType,
                $scopeId
            );
            $clientSecret = (string)$this->scopeConfig->getValue(
                self::WARRANTY_AUTHENTICATION_SANDBOX_CLIENT_SECRET_XML_PATH,
                $scopeType,
                $scopeId
            );
        }

        return ($clientId && $clientSecret) ? true : false;
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
    ) {
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
    ) {
        $scopeType = ScopeInterface::SCOPE_STORES;
        if ($this->isExtendOauthEnabled($scopeType, $scopeId)){
            
            //build ApiKey based on Oauth
            if ($this->isExtendLive($scopeType, $scopeId)){
                //live
                $clientId = (string)$this->scopeConfig->getValue(
                    self::WARRANTY_AUTHENTICATION_CLIENT_ID_XML_PATH,
                    $scopeType,
                    $scopeId
                );
                $clientSecret = (string)$this->scopeConfig->getValue(
                    self::WARRANTY_AUTHENTICATION_CLIENT_SECRET_XML_PATH,
                    $scopeType,
                    $scopeId
                );
                $oauthTokenDate = (string)$this->scopeConfig->getValue(
                    self::WARRANTY_AUTHENTICATION_OAUTH_TOKEN_DATE_XML_PATH,
                    $scopeType,
                    $scopeId
                );
                $apiKey = (string)$this->scopeConfig->getValue(
                    self::WARRANTY_AUTHENTICATION_API_KEY_XML_PATH,
                          $scopeType,
                          $scopeId);
                $apiUrl = (string)$this->scopeConfig->getValue(
                    self::WARRANTY_AUTHENTICATION_API_URL_XML_PATH,
                          $scopeType,
                          $scopeId);
            }else {
                //sandbox
                $clientId = (string)$this->scopeConfig->getValue(
                    self::WARRANTY_AUTHENTICATION_SANDBOX_CLIENT_ID_XML_PATH,
                    $scopeType,
                    $scopeId
                );
                $clientSecret = (string)$this->scopeConfig->getValue(
                    self::WARRANTY_AUTHENTICATION_SANDBOX_CLIENT_SECRET_XML_PATH,
                    $scopeType,
                    $scopeId
                );
                $oauthTokenDate = (string)$this->scopeConfig->getValue(
                    self::WARRANTY_AUTHENTICATION_SANDBOX_OAUTH_TOKEN_DATE_XML_PATH,
                    $scopeType,
                    $scopeId
                );
                $apiKey = (string)$this->scopeConfig->getValue(
                    self::WARRANTY_AUTHENTICATION_SANDBOX_API_KEY_XML_PATH,
                          $scopeType,
                          $scopeId);
                $apiUrl = (string)$this->scopeConfig->getValue(
                    self::WARRANTY_AUTHENTICATION_SANDBOX_API_URL_XML_PATH,
                          $scopeType,
                          $scopeId);
            }
            // return stored ApiKey if time is still below 3hr+15m
            if ($apiKey && ($oauthTokenDate && ((time() - $oauthTokenDate)  < 9900))) {
                return $apiKey;
            }else {
                // retrieve new token
                $url = $apiUrl . 'auth/oauth/token';

                $headers = array('Content-Type' => 'application/json', 'Accept' => 'application/json; version=latest');
                $data =  array(
                                        'grant_type'       => 'client_credentials',
                                        'client_id'        => $clientId,
                                        'client_secret'    => $clientSecret,
                                        'client_assertion' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'
                                    );
                $response = $this->connector->call($url, "POST", $headers, $data);

                $responseBody = [];
                $responseBodyJson = $response->getBody();
                $responseBody = $this->jsonSerializer->unserialize($responseBodyJson);

                if (array_key_exists('access_token', $responseBody)) {
                    // update token and oauthtoken date
                    $apiKey = (string)$responseBody['access_token'];
                    if ($this->isExtendLive($scopeType, $scopeId)){
                        $this->configResource->saveConfig(self::WARRANTY_AUTHENTICATION_API_KEY_XML_PATH,
                                                          $apiKey, $scopeType, (int)$scopeId);

                        $this->configResource->saveConfig(self::WARRANTY_AUTHENTICATION_OAUTH_TOKEN_DATE_XML_PATH,
                                                          time(), $scopeType, (int)$scopeId);

                    }else{
                        $this->configResource->saveConfig(self::WARRANTY_AUTHENTICATION_SANDBOX_API_KEY_XML_PATH,
                                                          $apiKey, $scopeType, (int)$scopeId);

                        $this->configResource->saveConfig(self::WARRANTY_AUTHENTICATION_SANDBOX_OAUTH_TOKEN_DATE_XML_PATH,
                                                          time(), $scopeType, (int)$scopeId);
                    }
                    $this->cacheManager->clean([Config::TYPE_IDENTIFIER]);
                } else {
                    $apiKey = null;
                }
                return $apiKey;
            }
        }else{
            //use Long Live Token
            $path = $this->isExtendLive($scopeType, $scopeId) ? self::WARRANTY_AUTHENTICATION_API_KEY_XML_PATH
                : self::WARRANTY_AUTHENTICATION_SANDBOX_API_KEY_XML_PATH;

            return (string)$this->scopeConfig->getValue($path, $scopeType, $scopeId);
        }

    }

    /**
     * Get Client ID
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return string
     */
    public function getClientId(
        string $scopeType = ScopeInterface::SCOPE_STORES,
               $scopeId = null
    ) {
        $path = $this->isExtendLive($scopeType, $scopeId) ? self::WARRANTY_AUTHENTICATION_CLIENT_ID_XML_PATH
            : self::WARRANTY_AUTHENTICATION_SANDBOX_CLIENT_ID_XML_PATH;

        return (string)$this->scopeConfig->getValue($path, $scopeType, $scopeId);
    }

    /**
     * Get Client Secret
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return string
     */
    public function getClientSecret(
        string $scopeType = ScopeInterface::SCOPE_STORES,
               $scopeId = null
    ) {
        $path = $this->isExtendLive($scopeType, $scopeId) ? self::WARRANTY_AUTHENTICATION_CLIENT_SECRET_XML_PATH
            : self::WARRANTY_AUTHENTICATION_SANDBOX_CLIENT_SECRET_XML_PATH;

        return (string)$this->scopeConfig->getValue($path, $scopeType, $scopeId);
    }

    /**
     * Get Access Token Age
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return string
     */
    public function getTokenAge(
        string $scopeType = ScopeInterface::SCOPE_STORES,
               $scopeId = null
    ) {
        $path =  self::WARRANTY_AUTHENTICATION_OAUTH_TOKEN_DATE_XML_PATH;
        return (string)$this->scopeConfig->getValue(
            $path,
            $scopeType,
            $scopeId
        );
    }

    /**
     * Get Access Token Age Sandbox
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return string
     */
    public function getTokenAgeSandBox(
        string $scopeType = ScopeInterface::SCOPE_STORES,
               $scopeId = null
    ) {
        $path = self::WARRANTY_AUTHENTICATION_SANDBOX_OAUTH_TOKEN_DATE_XML_PATH;

        return (string)$this->scopeConfig->getValue(
            $path,
            $scopeType,
            $scopeId
        );
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
    ) {
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
    public function isBalancedCart($storeId = null)
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
    public function isLoggingEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::WARRANTY_ENABLE_EXTEND_LOGGING_ENABLED_XML_PATH);
    }

    /**
     * Check if warranty contract creation for order item is enabled
     *
     * @param string|int|null $storeId
     * @return bool
     */
    public function isWarrantyContractEnabled($storeId = null)
    {
        if ($this->scopeConfig->getValue(
            self::WARRANTY_CONTRACTS_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId
        ) > 0
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get contract crete API
     *
     * @param string $scopeType
     * @param int|string|null $storeId
     * @return int
     */
    public function getContractCreateApi(
        string $scopeType = ScopeInterface::SCOPE_STORES,
        $storeId = null
    ) {
        return (int)$this->scopeConfig->getValue(
            self::WARRANTY_CONTRACTS_ENABLED_XML_PATH,
            $scopeType,
            $storeId
        );
    }

    /**
     * Get contract create event
     *
     * @param string $scopeType
     * @param $storeId
     * @return int
     */
    public function getContractCreateEvent(
        string $scopeType = ScopeInterface::SCOPE_STORES,
        $storeId = null
    ) {
        return (int)$this->scopeConfig->getValue(
            self::WARRANTY_CONTRACTS_EVENT_XML_PATH,
            $scopeType,
            $storeId
        );
    }

    /**
     * Get contract creating mode
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return bool
     */
    public function isContractCreateModeScheduled(
        $scopeId = null,
        string $scopeType = ScopeInterface::SCOPE_STORES
    ) {
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
    public function isRefundEnabled($storeId = null)
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
    public function isAutoRefundEnabled($storeId = null)
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
     * @return string
     */
    public function getContractFrequency($storeId = null)
    {
        return (string) $this->scopeConfig->getValue(
            self::WARRANTY_CONTRACTS_FREQUENCY_XML_PATH,
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
    public function getContractsBatchSize($storeId = null)
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
    public function getStoragePeriod($storeId = null)
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

    public function isShoppingAdminOffersEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::WARRANTY_OFFERS_ADMIN_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Check if shopping cart offers enabled
     *
     * @param string|int|null $storeId
     * @return bool
     */
    public function isShoppingCartOffersEnabled($storeId = null)
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
    public function isProductDetailPageOffersEnabled($storeId = null)
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
    public function isProductsListOffersEnabled($storeId = null)
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
    public function isInterstitialCartOffersEnabled($storeId = null)
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
    public function isLeadsModalEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::LEADS_MODAL_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Check if Order Warranty Information Offers enabled
     *
     * @param string|int|null $storeId
     * @return bool
     */
    public function isOrderOffersEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::ORDER_OFFERS_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Get PDP Offers Button placement config
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return int
     */
    public function getProductDetailPageOffersPlacement(
        string $scopeType = ScopeInterface::SCOPE_STORES,
               $scopeId = null
    ) {
        return (int)$this->scopeConfig->getValue(
            self::WARRANTY_OFFERS_PDP_PLACEMENT_XML_PATH,
            $scopeType,
            $scopeId
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
    ) {
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
    ) {
        return (string)$this->scopeConfig->getValue(
            self::WARRANTY_PRODUCTS_LAST_SYNC_DATE_XML_PATH,
            $scopeType,
            $scopeId
        );
    }

    /**
     * Check if product synchronization by cron is enabled
     */
    public function isProductSyncByCronEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::WARRANTY_PRODUCTS_CRON_SYNC_ENABLED_XML_PATH);
    }

    /**
     * Check if syncing product special prices is enabled
     */
    public function isProductSpecialPriceSyncEnabled(
        string $scopeType = ScopeInterface::SCOPE_STORES,
               $scopeId = null
    )
    {
        return $this->scopeConfig->isSetFlag(
            self::WARRANTY_PRODUCT_SYNC_SPECIAL_PRICES_XML_PATH,
            $scopeType,
            $scopeId
        );
    }

    public function getHistoricalOrdersBatchSize(
        string $scopeType = ScopeInterface::SCOPE_STORES,
               $scopeId = null
    ) {
        return (int)$this->scopeConfig->getValue(
            self::WARRANTY_HISTORICAL_ORDERS_BATCH_SIZE_XML_PATH,
            $scopeType,
            $scopeId
        );
    }

    /**
     * Set historical orders sync period
     *
     * @param string $value
     * @param string $scopeType
     * @param int|string|null $scopeId
     */
    public function setHistoricalOrdersSyncPeriod(
        string $value,
        string $scopeType = ScopeInterface::SCOPE_STORES,
               $scopeId = null
    ): void {
        $this->configResource->saveConfig(
            self::WARRANTY_HISTORICAL_ORDERS_SYNC_PERIOD_XML_PATH,
            $value,
            $scopeType,
            (int)$scopeId
        );
        $this->cacheManager->clean([Config::TYPE_IDENTIFIER]);
    }

    /**
     * Get historical orders sync period
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return string
     */
    public function getHistoricalOrdersSyncPeriod(
        string $scopeType = ScopeInterface::SCOPE_STORES,
               $scopeId = null
    ): string {
        return (string)$this->scopeConfig->getValue(
            self::WARRANTY_HISTORICAL_ORDERS_SYNC_PERIOD_XML_PATH,
            $scopeType,
            $scopeId
        );
    }

    /**
     * Check if historical orders synchronization by cron is enabled
     */
    public function isHistoricalOrdersCronSyncEnabled(
        string $scopeType = ScopeInterface::SCOPE_STORES,
               $scopeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::WARRANTY_HISTORICAL_ORDERS_CRON_SYNC_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $scopeId
        );
    }

    /**
     * Check if leads enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isLeadEnabled($storeId = null)
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
    ) {
        return (string)$this->scopeConfig->getValue(
            self::WARRANTY_AUTHENTICATION_STORE_NAME,
            $scopeType,
            $scopeId
        );
    }

}
