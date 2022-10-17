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
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\FileSystemException;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Model\ProductFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Extend\Warranty\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Model\Product\Gallery\EntryFactory;
use Magento\Catalog\Model\Product\Gallery\GalleryManagement;
use Magento\Framework\Api\ImageContentFactory;

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
    const WARRANTY_PRODUCT_SKU = 'WARRANTY-1';

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @var EntryFactory
     */
    private $mediaGalleryEntryFactory;

    /**
     * @var GalleryManagement
     */
    private $mediaGalleryManagement;

    /**
     * @var ImageContentFactory
     */
    private $imageContentFactory;

    /**
     * @var State
     */
    protected $appState;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param State $appState
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        File $file,
        Reader $reader,
        DirectoryList $directoryList,
        ProductFactory $productFactory,
        StoreManagerInterface $storeManager,
        EavSetupFactory $eavSetupFactory,
        EntryFactory $mediaGalleryEntryFactory,
        GalleryManagement $mediaGalleryManagement,
        ImageContentFactory $imageContentFactory,
        State $appState,
        LoggerInterface $logger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->productRepository = $productRepository;
        $this->file = $file;
        $this->reader = $reader;
        $this->directoryList = $directoryList;
        $this->productFactory = $productFactory;
        $this->storeManager = $storeManager;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->mediaGalleryEntryFactory = $mediaGalleryEntryFactory;
        $this->mediaGalleryManagement = $mediaGalleryManagement;
        $this->imageContentFactory = $imageContentFactory;
        $this->appState = $appState;
        $this->logger = $logger;
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
    public function validateWarrantyProduct(OutputInterface $output)
    {
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
    private function getWarrantyProduct()
    {
        try {
            return $this->productRepository->get(self::WARRANTY_PRODUCT_SKU, false, Store::DEFAULT_STORE_ID);
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
    private function checkWarrantyProductType(ProductInterface $warranty)
    {
        return $warranty->getTypeId() === WarrantyType::TYPE_CODE;
    }

    /**
     * Check warranty product status
     *
     * @param ProductInterface $warranty
     * @return bool
     */
    private function checkWarrantyProductStatus(ProductInterface $warranty)
    {
        return (int)$warranty->getStatus() !== ProductStatus::STATUS_DISABLED;
    }

    /**
     * Add warranty product
     *
     * @return void
     * @throws FileSystemException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function addWarrantyProduct()
    {
        $this->addImageToPubMedia();
        $this->createWarrantyProduct();
        $this->enablePriceForWarrantyProducts();
    }

    /**
     * Get image to pub media
     *
     * @return void
     *
     * @throws FileSystemException
     */
    private function addImageToPubMedia()
    {
        $imagePath = $this->reader->getModuleDir('', 'Extend_Warranty');
        $imagePath .= '/Setup/Resource/Extend_icon.png';
        $media = $this->directoryList->getPath('media') . '/Extend_icon.png';
        $this->file->cp($imagePath, $media);
    }

    /**
     * Create warranty product
     *
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function createWarrantyProduct()
    {
        $warranty = $this->productFactory->create();

        $warranty->setSku(self::WARRANTY_PRODUCT_SKU)
            ->setName('Extend Protection Plan')
            ->setWebsiteIds(array_keys($this->storeManager->getWebsites()))
            ->setAttributeSetId($warranty->getDefaultAttributeSetId())
            ->setStatus(Status::STATUS_ENABLED)
            ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
            ->setTypeId(Type::TYPE_CODE)
            ->setPrice(0.0)
            ->setTaxClassId(0) //None
            ->setCreatedAt(strtotime('now'))
            ->setStockData([
                'use_config_manage_stock' => 0,
                'is_in_stock' => 1,
                'qty' => 1,
                'manage_stock' => 0,
                'use_config_notify_stock_qty' => 0
            ]);


        $this->productRepository->save($warranty);
        $this->processMediaGalleryEntry();
    }

    /**
     * Process media gallery entry
     *
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function processMediaGalleryEntry()
    {
        $filePath = $this->reader->getModuleDir('', 'Extend_Warranty') . '/Setup/Resource/Extend_icon.png';
        $entry = $this->mediaGalleryEntryFactory->create();
        $entry->setFile($filePath);
        $entry->setMediaType('image');
        $entry->setDisabled(false);
        $entry->setTypes(['thumbnail', 'image', 'small_image']);

        $imageContent = $this->imageContentFactory->create();
        $imageContent
            ->setType(mime_content_type($filePath))
            ->setName('Extend Protection Plan')
            ->setBase64EncodedData(base64_encode(file_get_contents($filePath)));

        $entry->setContent($imageContent);

        $this->mediaGalleryManagement->create(self::WARRANTY_PRODUCT_SKU, $entry);
    }

    /**
     * MAKE PRICE ATTRIBUTE AVAILABLE FOR WARRANTY PRODUCT TYPE
     *
     * @return void
     */
    private function enablePriceForWarrantyProducts()
    {
        $eavSetup = $this->eavSetupFactory->create();

        $fieldList = [
            'price',
            'special_price',
            'tier_price',
            'minimal_price'
        ];

        foreach ($fieldList as $field) {
            $applyTo = explode(
                ',',
                $eavSetup->getAttribute(Product::ENTITY, $field, 'apply_to')
            );

            if (empty($applyTo) || count($applyTo) <= 1) {
                $defaultApplyTo = $field !== 'tier_price'
                    ? 'simple,virtual,configurable,downloadable,bundle'
                    : 'simple,virtual,bundle,downloadable';
                $applyTo = explode(',', $defaultApplyTo);
            }

            if (!in_array(Type::TYPE_CODE, $applyTo)) {
                $applyTo[] = Type::TYPE_CODE;
                $eavSetup->updateAttribute(
                    Product::ENTITY,
                    $field,
                    'apply_to',
                    implode(',', $applyTo)
                );
            }
        }
    }
}
