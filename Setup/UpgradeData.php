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

namespace Extend\Warranty\Setup;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Extend\Warranty\Model\Product\Type;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Area;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Psr\Log\LoggerInterface;
use Exception;
use \Magento\InventoryApi\Api\StockRepositoryInterface;
use \Magento\InventoryApi\Api\SourceRepositoryInterface;


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
     * @var SourceItemInterface
     */
    private $sourceRepository;

    /**
     * Product Repository Interface
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Search Criteria Builder
     *
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param AppState $appState
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $taxSetupFactory
     * @param LoggerInterface $logger
     * @param SourceRepositoryInterface $sourceRepository
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        AppState                   $appState,
        ModuleDataSetupInterface   $moduleDataSetup,
        EavSetupFactory            $taxSetupFactory,
        LoggerInterface            $logger,
        SourceRepositoryInterface  $sourceRepository,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder      $searchCriteriaBuilder
    )
    {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->appState = $appState;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $taxSetupFactory;
        $this->logger = $logger;
        $this->sourceRepository = $sourceRepository;
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
        if (version_compare($context->getVersion(), '1.2.5', '<')) {
            $this->appState->emulateAreaCode(
                Area::AREA_ADMINHTML,
                [$this, 'updateMultiInventoryStock'],
                [$setup]
            );
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

    public function updateMultiInventoryStock(ModuleDataSetupInterface $setup)
    {
        $sourceList = $this->sourceRepository->getList();
        $product = $this->getWarrantyProduct();
        /** @var SourceInterface $source */
        foreach ($sourceList->getItems() as $source) {
            $sourceCode = $source->getSourceCode();

            $insertData = [
                'source_code' => $sourceCode,
                'sku' => $product->getSku(),
                'quantity' => 1,
                'status' => 1
            ];

            $setup->getConnection()->insertOnDuplicate(
                $setup->getTable('inventory_source_item'),
                $insertData,
                [
                    SourceItemInterface::SOURCE_CODE,
                    SourceItemInterface::QUANTITY,
                    SourceItemInterface::STATUS,
                    SourceItemInterface::SKU,
                ]
            );
        }
    }

    /**
     * @return Product
     */
    private function getWarrantyProduct()
    {
        $this->searchCriteriaBuilder->setPageSize(1);
        $this->searchCriteriaBuilder->addFilter(ProductInterface::TYPE_ID, Type::TYPE_CODE);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResults = $this->productRepository->getList($searchCriteria);
        $results = $searchResults->getItems();

        return reset($results);
    }
}
