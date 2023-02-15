<?php
// phpcs:ignoreFile -- UpgradeData scripts are obsolete
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Setup;

use Magento\Catalog\Model\Product;
use Extend\Warranty\Model\Product\Type;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Area;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Psr\Log\LoggerInterface;
use Exception;
use Magento\Backend\Block\System\Store\Store;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Extend\Warranty\Helper\Api\Data;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Class UpgradeData
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * Attribute code
     */
    const TAX_CLASS_ID_ATTR_CODE = 'tax_class_id';

    /**
     * App State
     *
     * @var AppState
     */
    private $appState;

    /**
     * Eav Setup Factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Module Data Setup Interface
     *
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

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
     * UpgradeData constructor
     *
     * @param AppState $appState
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $taxSetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        AppState $appState,
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $taxSetupFactory,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        Data $apiHelper,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $writer
    ) {
        $this->appState = $appState;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $taxSetupFactory;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->apiHelper = $apiHelper;
        $this->scopeConfig = $scopeConfig;
        $this->writer = $writer;
    }

    /**
     * Upgrade data
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws Exception
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.2.4', '<')) {
            $this->appState->emulateAreaCode(
                Area::AREA_ADMINHTML,
                [$this, 'applyTaxClassAttrToWarrantyProduct']
            );
        }

        if (version_compare($context->getVersion(), '1.3.0', '<')) {
            $this->removeContractsApiConfiguration();
        }

        if (version_compare($context->getVersion(), '1.3.1', '<')) {
            $this->removeLastSyncValue();
        }
    }

    /**
     * Apply tax class attribute to warranty product type
     */
    public function applyTaxClassAttrToWarrantyProduct()
    {
        try {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $taxClassIdApplyTo = $eavSetup->getAttribute(
                Product::ENTITY,
                self::TAX_CLASS_ID_ATTR_CODE,
                'apply_to'
            );

            if ($taxClassIdApplyTo) {
                $productTypes = explode(',', $taxClassIdApplyTo);
                if (!in_array(Type::TYPE_CODE, $productTypes)) {
                    $productTypes[] = Type::TYPE_CODE;
                    $updatedTaxClassIdApplyTo = implode(',', $productTypes);

                    $eavSetup->updateAttribute(
                        Product::ENTITY,
                        self::TAX_CLASS_ID_ATTR_CODE,
                        ['apply_to' => $updatedTaxClassIdApplyTo]
                    );
                }
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    public function removeContractsApiConfiguration() {
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
    }

    public function removeLastSyncValue() {
        $this->writer->delete(Data::WARRANTY_PRODUCTS_LAST_SYNC_DATE_XML_PATH, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }
}
