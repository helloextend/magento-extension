<?php
namespace Extend\Warranty\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

use Magento\Config\Model\ResourceModel\Config as ConfigResource;


class InstallData implements InstallDataInterface
{
    //protected $configWriter;
    protected $scopeConfig;
    protected $storeManager;
    /**
     * Config Resource Model
     *
     * @var ConfigResource
     */
    private $configResource;


    public const WARRANTY_AUTHENTICATION_CLIENT_ID_XML_PATH = 'warranty/authentication/client_id';
    public const WARRANTY_AUTHENTICATION_SANDBOX_CLIENT_ID_XML_PATH = 'warranty/authentication/sandbox_client_id';
    public const WARRANTY_AUTHENTICATION_CLIENT_SECRET_XML_PATH = 'warranty/authentication/client_secret';
    public const WARRANTY_AUTHENTICATION_SANDBOX_CLIENT_SECRET_XML_PATH = 'warranty/authentication/sandbox_client_secret';
    public const WARRANTY_AUTHENTICATION_API_KEY_XML_PATH = 'warranty/authentication/api_key';
    public const WARRANTY_AUTHENTICATION_SANDBOX_API_KEY_XML_PATH = 'warranty/authentication/sandbox_api_key';


    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ConfigResource $configResource,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->configResource = $configResource;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        // Get all store views
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            $storeId = $store->getId();

            // Fetch the values for each store view
            $clientId            = $this->scopeConfig->getValue(self::WARRANTY_AUTHENTICATION_CLIENT_ID_XML_PATH,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeId);
            $clientSecret        = $this->scopeConfig->getValue(self::WARRANTY_AUTHENTICATION_CLIENT_SECRET_XML_PATH,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeId);
            $apiKey              = $this->scopeConfig->getValue(self::WARRANTY_AUTHENTICATION_API_KEY_XML_PATH,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeId);
            $sandboxClientId     = $this->scopeConfig->getValue(self::WARRANTY_AUTHENTICATION_SANDBOX_CLIENT_ID_XML_PATH,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeId);
            $sandboxClientSecret = $this->scopeConfig->getValue(self::WARRANTY_AUTHENTICATION_SANDBOX_CLIENT_SECRET_XML_PATH,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeId);
            $sandboxApiKey       = $this->scopeConfig->getValue(self::WARRANTY_AUTHENTICATION_SANDBOX_API_KEY_XML_PATH,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeId);

            // Check the conditions and delete the config if necessary
            if (empty($clientId) && empty($clientSecret) && !empty($apiKey)) {
                $this->configResource->deleteConfig(self::WARRANTY_AUTHENTICATION_API_KEY_XML_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeId);
            }

            if (empty($sandboxClientId) && empty($sandboxClientSecret) && !empty($sandboxApiKey)) {
                $this->configResource->deleteConfig(self::WARRANTY_AUTHENTICATION_SANDBOX_API_KEY_XML_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeId);
            }

        }
    }
}
