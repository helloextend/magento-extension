<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Console\Command;

use Exception;
use Extend\Warranty\Model\Product\Type as WarrantyType;
use Extend\Warranty\Setup\Patch\Data\AddWarrantyProductPatch;
use Extend\Warranty\Setup\Patch\Data\AddWarrantyProductPatch as WarrantyCreate;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\FileSystemException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \Magento\Catalog\Model\ResourceModel\ProductFactory as ResourceProductFactory;

/**
 * Class CreateWarrantyProduct
 *
 * Create warranty product Console Command
 */
class CreateWarrantyProduct extends Command
{
    /**
     * warranty product sku
     */
    protected const WARRANTY_PRODUCT_SKU = 'WARRANTY-1';

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var WarrantyCreate
     */
    protected $warrantyProductCreate;

    /**
     * @var State
     */
    protected $appState;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ResourceProductFactory
     */
    protected $resourceProductFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param WarrantyCreate $warrantyProductCreate
     * @param State $appState
     * @param LoggerInterface $logger
     * @param ResourceProductFactory $resourceProductFactory
     * @param StoreManagerInterface $storeManager
     * @param string|null $name
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        WarrantyCreate             $warrantyProductCreate,
        State                      $appState,
        LoggerInterface            $logger,
        ResourceProductFactory     $resourceProductFactory,
        StoreManagerInterface      $storeManager,
        string                     $name = null
    ) {
        parent::__construct($name);
        $this->productRepository = $productRepository;
        $this->warrantyProductCreate = $warrantyProductCreate;
        $this->appState = $appState;
        $this->logger = $logger;
        $this->resourceProductFactory = $resourceProductFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('extend:catalog:create_warranty_item');
        $this->setDescription('Create or restore warranty-1 product');
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->emulateAreaCode(
                Area::AREA_ADMINHTML,
                [$this, 'validateWarrantyProduct'],
                [$output]
            );
        } catch (Exception $exception) {
            $output->writeln("Something went wrong while creating the warranty-1 product.");
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * Validate warranty product
     *
     * @param OutputInterface $output
     * @return void
     * @throws FileSystemException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function validateWarrantyProduct(OutputInterface $output): void
    {
        $this->storeManager->setCurrentStore(Store::DEFAULT_STORE_ID);
        $warrantyProduct = $this->getWarrantyProduct();

        if ($warrantyProduct) {
            if (!$this->checkWarrantyProductType($warrantyProduct)) {
                $warrantyProduct->setTypeId(WarrantyType::TYPE_CODE);
                $this->productRepository->save($warrantyProduct);
                $output->writeln("Warranty product type is changed to " . WarrantyType::TYPE_CODE);
                $this->logger->info("Warranty product type is changed to " . WarrantyType::TYPE_CODE);
            }

            if ($this->checkWarrantyProductStatus($warrantyProduct)) {
                $output->writeln("Warranty product was exist and enabled");
                $this->logger->info("Warranty product was exist and enabled");
            } else {
                $this->clearStatusAttributeValues($warrantyProduct);
                $warrantyProduct->setStatus(ProductStatus::STATUS_ENABLED);
                $this->productRepository->save($warrantyProduct);
                $output->writeln("Warranty product is enabled");
                $this->logger->info("Warranty product is enabled");
            }
        } else {
            $this->createWarrantyProduct();
            $output->writeln("Warranty product is created");
            $this->logger->info("Warranty product is created");
        }
    }

    /**
     * Get warranty product
     *
     * @return ProductInterface|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getWarrantyProduct(): ?ProductInterface
    {
        try {
            return $this->productRepository->get(self::WARRANTY_PRODUCT_SKU, true, Store::DEFAULT_STORE_ID);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check warranty product type
     *
     * @param ProductInterface $warranty
     * @return bool
     */
    private function checkWarrantyProductType(ProductInterface $warranty): bool
    {
        return $warranty->getTypeId() === WarrantyType::TYPE_CODE;
    }

    /**
     * Check warranty product status
     *
     * @param ProductInterface $warranty
     * @return bool
     */
    private function checkWarrantyProductStatus(ProductInterface $warranty): bool
    {
        return (int)$warranty->getStatus() !== ProductStatus::STATUS_DISABLED;
    }

    /**
     * Create warranty product
     *
     * @return void
     * @throws FileSystemException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function createWarrantyProduct(): void
    {
        $this->warrantyProductCreate->addImageToPubMedia();
        $this->warrantyProductCreate->createWarrantyProduct();
        $this->warrantyProductCreate->enablePriceForWarrantyProducts();
    }

    /**
     *
     * Clearing status attribute values for warranties product in sub stores
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     */
    private function clearStatusAttributeValues($product)
    {
        $attributeCode = 'status';
        $productResource = $this->resourceProductFactory->create();

        $entityIdField = $productResource->getLinkField();
        $entityId = $product->getData($entityIdField);

        $connection = $productResource->getConnection();

        $statusAttribute = $productResource->getAttribute($attributeCode);

        $where = $connection->quoteInto('attribute_id = ?', $statusAttribute->getId());
        $where .= $connection->quoteInto(" AND {$entityIdField} = ?", $entityId);
        $where .= $connection->quoteInto(' AND store_id != ?', Store::DEFAULT_STORE_ID);

        $connection->delete($statusAttribute->getBackendTable(), $where);
    }
}
