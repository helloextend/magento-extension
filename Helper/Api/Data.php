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

namespace Extend\Warranty\Helper\Api;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Type\Config;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
    /**
     * General settings
     */
    const BASEPATH = 'warranty/authentication/';
    const ENABLE_PATH = 'warranty/enableExtend/';

    const LOGGING_ENABLED_XML_PATH = 'warranty/enableExtend/logging_enabled';
    CONST PRODUCTS_PATH = 'warranty/products/';

    /**
     * Contracts
     */
    const WARRANTY_CONTRACTS_ENABLED_XML_PATH = 'warranty/contracts/enabled';
    const WARRANTY_CONTRACTS_BATCH_SIZE_XML_PATH = 'warranty/contracts/batch_size';
    const WARRANTY_CONTRACTS_STORAGE_PERIOD_XML_PATH = 'warranty/contracts/storage_period';
    const WARRANTY_CONTRACTS_REFUND_ENABLED_XML_PATH = 'warranty/enableExtend/enableRefunds';
    const WARRANTY_CONTRACTS_AUTO_REFUND_ENABLED_XML_PATH = 'warranty/contracts/auto_refund_enabled';

    /**
     * Offers
     */
    const WARRANTY_OFFERS_SHOPPING_CART_ENABLED_XML_PATH = 'warranty/enableExtend/enableCartOffers';
    const WARRANTY_OFFERS_PDP_ENABLED_XML_PATH = 'warranty/offers/pdp_enabled';
    const WARRANTY_OFFERS_INTERSTITIAL_CART_ENABLED_XML_PATH = 'warranty/offers/interstitial_cart_enabled';

    /**
     * Products
     */
    const WARRANTY_PRODUCTS_BATCH_SIZE_XML_PATH = 'warranty/products/batch_size';
    const WARRANTY_PRODUCTS_LAST_SYNC_DATE_XML_PATH = 'warranty/products/lastSync';
    const WARRANTY_PRODUCTS_CRON_SYNC_ENABLED_XML_PATH = 'warranty/products/cron_sync_enabled';
    const WARRANTY_PRODUCTS_CRON_LAST_SYNC_DATE_XML_PATH = 'warranty/products/cron_last_sync_date';

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
     * Cache Type List
     *
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * Data constructor
     *
     * @param Context $context
     * @param ModuleListInterface $moduleList
     * @param ConfigResource $configResource
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        ConfigResource $configResource,
        TypeListInterface $cacheTypeList
    ) {
        $this->moduleList = $moduleList;
        $this->configResource = $configResource;
        $this->cacheTypeList = $cacheTypeList;
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
     * Get value
     *
     * @param string $field
     * @return string
     */
    public function getValue(string $field): string
    {
        $path = self::BASEPATH . $field;

        return (string)$this->scopeConfig->getValue($path);
    }

    /**
     * Check if extend enabled
     *
     * @return bool
     */
    public function isExtendEnabled(): bool
    {
        $path = self::ENABLE_PATH . 'enable';

        return $this->scopeConfig->isSetFlag($path);
    }

    /**
     * Check if Extend live
     *
     * @return bool
     */
    public function isExtendLive(): bool
    {
        $path = self::BASEPATH . 'auth_mode';

        return $this->scopeConfig->isSetFlag($path);
    }

    /**
     * Check if cart balance enabled
     *
     * @return bool
     */
    public function isBalancedCart(): bool
    {
        $path = self::ENABLE_PATH . 'enableBalance';

        return $this->scopeConfig->isSetFlag($path);
    }

    /**
     * Check if logging enabled
     *
     * @param string|int|null $websiteId
     * @return bool
     */
    public function isLoggingEnabled($websiteId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::LOGGING_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );
    }

    public function isLeadEnabled()
    {
        $path = self::ENABLE_PATH . 'enableLeads';
        return $this->scopeConfig->isSetFlag($path);
    }

    /**
     * Check if warranty contract creation for order item is enabled
     *
     * @param string|int|null $websiteId
     * @return bool
     */
    public function isWarrantyContractEnabled($websiteId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::WARRANTY_CONTRACTS_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
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
     * @param string|int|null $websiteId
     * @return bool
     */
    public function isAutoRefundEnabled($websiteId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::WARRANTY_CONTRACTS_AUTO_REFUND_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );
    }

    /**
     * Get contracts batch size
     *
     * @return int
     */
    public function getContractsBatchSize(): int
    {
        return (int)$this->scopeConfig->getValue(self::WARRANTY_CONTRACTS_BATCH_SIZE_XML_PATH);
    }

    /**
     * Get storage period, days
     *
     * @return int
     */
    public function getStoragePeriod(): int
    {
        return (int)$this->scopeConfig->getValue(self::WARRANTY_CONTRACTS_STORAGE_PERIOD_XML_PATH);
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
     * Get products batch size
     *
     * @return int
     */
    public function getProductsBatchSize(): int
    {
        return (int)$this->scopeConfig->getValue(self::WARRANTY_PRODUCTS_BATCH_SIZE_XML_PATH);
    }

    /**
     * Set last product sync date
     *
     * @param string $value
     */
    public function setLastProductSyncDate(string $value): void
    {
        $this->configResource->saveConfig(self::WARRANTY_PRODUCTS_LAST_SYNC_DATE_XML_PATH, $value);
        $this->cacheTypeList->invalidate(Config::TYPE_IDENTIFIER);
    }

    /**
     * Get last product sync date
     *
     * @return string
     */
    public function getLastProductSyncDate(): string
    {
        return (string)$this->scopeConfig->getValue(self::WARRANTY_PRODUCTS_LAST_SYNC_DATE_XML_PATH);
    }

    /**
     * Check if product synchronization by cron is enabled
     *
     * @return bool
     */
    public function isProductSyncByCronEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::WARRANTY_PRODUCTS_CRON_SYNC_ENABLED_XML_PATH);
    }

    /**
     * Set last product sync date by cron
     *
     * @param string $value
     */
    public function setCronLastProductSyncDate(string $value): void
    {
        $this->configResource->saveConfig(self::WARRANTY_PRODUCTS_CRON_LAST_SYNC_DATE_XML_PATH, $value);
        $this->cacheTypeList->invalidate(Config::TYPE_IDENTIFIER);
    }

    /**
     * Get last product sync date by cron
     *
     * @return string
     */
    public function getCronLastProductSyncDate(): string
    {
        return (string)$this->scopeConfig->getValue(self::WARRANTY_PRODUCTS_CRON_LAST_SYNC_DATE_XML_PATH);
    }
}
