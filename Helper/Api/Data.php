<?php

namespace Extend\Warranty\Helper\Api;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    CONST BASEPATH = 'warranty/authentication/';

    CONST ENABLE_PATH = 'warranty/enableExtend/';

    /**
     * Ajax Suite settings
     */
    const AJAXSUITE_GENERAL_ENABLED_XML_PATH = 'ajaxsuite/general/enabled';
    const AJAXSUITE_AJAXCART_ENABLED_XML_PATH = 'ajaxsuite/ajaxcart/enabled';

    protected $scopeConfig;

    public function __construct
    (
        Context $context,
        ScopeConfigInterface $config
    )
    {
        $this->scopeConfig = $config;
        parent::__construct($context);
    }

    public function getValue(string $field)
    {
        $path = self::BASEPATH . $field;
        return $this->scopeConfig->getValue($path);
    }

    public function isExtendEnabled()
    {
        $path = self::ENABLE_PATH . 'enable';
        return $this->scopeConfig->isSetFlag($path);
    }

    public function isExtendLive()
    {
        $path = self::BASEPATH . 'auth_mode';
        return $this->scopeConfig->isSetFlag($path);
    }

    public function isBalancedCart()
    {
        $path = self::ENABLE_PATH . 'enableBalance';
        return $this->scopeConfig->isSetFlag($path);
    }

    public function isDisplayOffersEnabled() {
        $path = self::ENABLE_PATH. 'enableCartOffers';
        return $this->scopeConfig->isSetFlag($path);
    }

    public function isRefundEnabled() {
        $path = self::ENABLE_PATH. 'enableRefunds';
        return $this->scopeConfig->isSetFlag($path);
    }

    /**
     * Check if ajax suite enabled
     *
     * @param string|int|null $storeId
     * @return bool
     */
    public function isAjaxSuiteEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::AJAXSUITE_GENERAL_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Check if ajax suite cart enabled
     * 
     * @param string|int|null $storeId
     * @return bool
     */
    public function isAjaxSuiteCartEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::AJAXSUITE_AJAXCART_ENABLED_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }
}