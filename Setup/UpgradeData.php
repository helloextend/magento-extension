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

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Area;
use Magento\Store\Model\Store;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Class UpgradeData
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * Warranty product sku
     */
    const WARRANTY_SKU = 'WARRANTY-1';
    
    /**
     * Product Repository Interface
     * 
     * @var ProductRepositoryInterface 
     */
    private $productRepository;

    /**
     * Category Repository Interface
     *
     * @var CategoryRepositoryInterface
     */
    private $categoryCollectionFactory;

    /**
     * Category Link Management Interface
     *
     * @var CategoryLinkManagementInterface
     */
    private $categoryLinkManagement;

    /**
     * App State
     *
     * @var AppState
     */
    private $appState;

    /**
     * App Emulation
     *
     * @var AppEmulation
     */
    private $appEmulation;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UpgradeData constructor
     *
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     * @param AppEmulation $appEmulation
     * @param AppState $appState
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryLinkManagementInterface $categoryLinkManagement,
        AppEmulation $appEmulation,
        AppState $appState,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->appState = $appState;
        $this->appEmulation = $appEmulation;
        $this->logger = $logger;
    }

    /**
     * Upgrade Data
     * 
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context): void
    {
        if (version_compare($context->getVersion(), '1.2.2', '<')) {
            $this->appState->emulateAreaCode(
                Area::AREA_ADMINHTML,
                [$this, 'updateWarrantyProduct']
            );
        }
    }

    /**
     * Update warranty product
     */
    public function updateWarrantyProduct(): void
    {
        try {
            $this->appEmulation->startEnvironmentEmulation(Store::DEFAULT_STORE_ID, Area::AREA_ADMINHTML);

            $warranty = $this->productRepository->get(self::WARRANTY_SKU);
            $warranty->setStatus(Status::STATUS_ENABLED);
            $warranty->setPrice(0.0);
            $this->productRepository->save($warranty);

            $categoryCollection = $this->categoryCollectionFactory->create();
            $categoryCollection->addFieldToSelect('entity_id');
            $categoryCollection->addLevelFilter(1);
            $categoryIds = $categoryCollection->getItems();

            $this->categoryLinkManagement->assignProductToCategories(self::WARRANTY_SKU, array_keys($categoryIds));

            $this->appEmulation->stopEnvironmentEmulation();
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}