<?php

namespace Extend\Warranty\Helper\Api;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\ModuleListInterface;

class Data extends AbstractHelper
{
    CONST BASEPATH = 'warranty/authentication/';
    CONST ENABLE_PATH = 'warranty/enableExtend/';
    CONST PRODUCTS_PATH = 'warranty/products/';
    CONST MODULE_NAME = 'Extend_Warranty';

    protected $scopeConfig;

    protected $moduleList;

    public function __construct
    (
        Context $context,
        ScopeConfigInterface $config,
        ModuleListInterface $moduleList
    )
    {
        $this->scopeConfig = $config;
        $this->moduleList = $moduleList;
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
        $path = self::ENABLE_PATH . 'enableCartOffers';
        return $this->scopeConfig->isSetFlag($path);
    }

    public function isRefundEnabled() {
        $path = self::ENABLE_PATH . 'enableRefunds';
        return $this->scopeConfig->isSetFlag($path);
    }

    public function isProductSyncByCronJobEnabled() {
        $path = self::PRODUCTS_PATH . 'enable_cronjob';
        return $this->scopeConfig->isSetFlag($path);
    }

    public function isLeadEnabled() {
        $path = self::ENABLE_PATH . 'enableLeads';
        return $this->scopeConfig->isSetFlag($path);
    }

    public function getVersion() {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }
}
