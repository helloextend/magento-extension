<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Setup\Patch\Data;

use Magento\Backend\Block\System\Store\Store;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Extend\Warranty\Helper\Api\Data;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * class RemoveContractApiConfiguration
 *
 * Remove any instances of configuration using the Contracts Api
 */
class RemoveContractApiConfiguration implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Data
     */
    private $apiHelper;

    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param StoreManagerInterface $storeManager
     * @param Data $apiHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $writer
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        StoreManagerInterface $storeManager,
        Data $apiHelper,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $writer
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->storeManager = $storeManager;
        $this->apiHelper = $apiHelper;
        $this->scopeConfig = $scopeConfig;
        $this->writer = $writer;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        foreach ($this->storeManager->getStores(true) as $store) {
            if ($this->apiHelper->getContractCreateApi(ScopeInterface::SCOPE_STORES, $store->getId()) === \Extend\Warranty\Model\Config\Source\CreateContractApi::CONTACTS_API) {
                $this->writer->delete(Data::WARRANTY_CONTRACTS_ENABLED_XML_PATH, ScopeInterface::SCOPE_STORES, $store->getId());
            }
        }

        foreach ($this->storeManager->getWebsites(true) as $website) {
            if ($this->apiHelper->getContractCreateApi(ScopeInterface::SCOPE_WEBSITES, $website->getId()) === \Extend\Warranty\Model\Config\Source\CreateContractApi::CONTACTS_API) {
                $this->writer->delete(Data::WARRANTY_CONTRACTS_ENABLED_XML_PATH, ScopeInterface::SCOPE_WEBSITES, $website->getId());
            }
        }

        if ($this->apiHelper->getContractCreateApi(ScopeConfigInterface::SCOPE_TYPE_DEFAULT) === \Extend\Warranty\Model\Config\Source\CreateContractApi::CONTACTS_API) {
            $this->writer->delete(Data::WARRANTY_CONTRACTS_ENABLED_XML_PATH, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }
}
