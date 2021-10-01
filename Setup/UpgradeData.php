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

use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Area;
use Magento\Store\Model\Store;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Extend\Warranty\Model\Product\Type;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Exception;

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
     * Warranty product name
     */
    const WARRANTY_NAME = 'Extend Protection Plan';

    /**
     * Product Factory
     *
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * Product Repository Interface
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Category Collection Factory
     *
     * @var CategoryCollectionFactory
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
     * Store Manager Interface
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UpgradeData constructor
     *
     * @param ProductFactory $productFactory
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     * @param AppEmulation $appEmulation
     * @param AppState $appState
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryLinkManagementInterface $categoryLinkManagement,
        AppEmulation $appEmulation,
        AppState $appState,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->appState = $appState;
        $this->appEmulation = $appEmulation;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Upgrade Data
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws Exception
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

            try {
                $warranty = $this->productRepository->get(self::WARRANTY_SKU);
            } catch (LocalizedException $exception) {
                $warranty = null;
            }

            if ($warranty) {
                $categoryCollection = $this->categoryCollectionFactory->create();
                $categoryCollection->addFieldToSelect('entity_id');
                $categoryCollection->addLevelFilter(2);
                $categoryIds = $categoryCollection->getItems();

                $warranty->setCategoryIds(array_keys($categoryIds));
                $warranty->setStatus(Status::STATUS_ENABLED);
                $warranty->setPrice(0.0);
                $this->productRepository->save($warranty);
            } else {
                $this->createWarrantyProduct();
            }

            $this->appEmulation->stopEnvironmentEmulation();
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * Create warranty product
     *
     * @throws LocalizedException
     */
    public function createWarrantyProduct(): void
    {
        try {
            $warranty = $this->productFactory->create();

            $websites = $this->storeManager->getWebsites();
            $attributeSetId = $warranty->getDefaultAttributeSetId();

            $categoryCollection = $this->categoryCollectionFactory->create();
            $categoryCollection->addFieldToSelect('entity_id');
            $categoryCollection->addLevelFilter(2);
            $categoryIds = $categoryCollection->getItems();

            $warranty->setSku(self::WARRANTY_SKU);
            $warranty->setName(self::WARRANTY_NAME);
            $warranty->setAttributeSetId($attributeSetId);
            $warranty->setWebsiteIds(array_keys($websites));
            $warranty->setStatus(Status::STATUS_ENABLED);
            $warranty->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);
            $warranty->setTypeId(Type::TYPE_CODE);
            $warranty->setPrice(0.0);
            $warranty->setCategoryIds(array_keys($categoryIds));
            $warranty->setStockData([
                'use_config_manage_stock' => 0,
                'is_in_stock' => 1,
                'qty' => 1,
                'manage_stock' => 0,
                'use_config_notify_stock_qty' => 0,
            ]);

            $imagePath = 'Extend_icon.png';
            $warranty->addImageToMediaGallery($imagePath, array('image', 'small_image', 'thumbnail'), false, false);

            $this->productRepository->save($warranty);
        } catch (Exception $exception) {
            throw new LocalizedException(__($exception->getMessage()));
        }
    }
}
