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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository\MediaGalleryProcessor;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\NonTransactionableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Model\ProductFactory;
use Extend\Warranty\Model\Product\Type;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\Product\Gallery\EntryFactory;
use Magento\Catalog\Model\Product\Gallery\GalleryManagement;
use Magento\Framework\Api\ImageContentFactory;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;

/**
 * class AddWarrantyProductPatch
 *
 * Add warranty product to catalog
 */
class AddWarrantyProductPatch implements DataPatchInterface, PatchRevertableInterface, NonTransactionableInterface
{
    /**
     * warranty product sku
     */
    protected const WARRANTY_PRODUCT_SKU = 'WARRANTY-1';

    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var EavSetup
     */
    protected $eavSetup;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

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
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

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
    private $state;

    /**
     * @var MediaGalleryProcessor
     */
    private $mediaProcessor;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    const MAGENTO_REPOSITORY_ISSUE_VERSIONS = ['2.4.0','2.4.1','2.4.1-p1','2.4.0-p1'];

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ProductFactory $productFactory
     * @param EavSetupFactory $eavSetupFactory
     * @param StoreManagerInterface $storeManager
     * @param File $file
     * @param Reader $reader
     * @param DirectoryList $directoryList
     * @param ProductRepositoryInterface $productRepository
     * @param EntryFactory $mediaGalleryEntryFactory
     * @param GalleryManagement $mediaGalleryManagement
     * @param ImageContentFactory $imageContentFactory
     * @param State $state
     * @throws LocalizedException
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ProductFactory $productFactory,
        EavSetupFactory $eavSetupFactory,
        StoreManagerInterface $storeManager,
        File $file,
        Reader $reader,
        DirectoryList $directoryList,
        ProductRepositoryInterface $productRepository,
        EntryFactory $mediaGalleryEntryFactory,
        GalleryManagement $mediaGalleryManagement,
        ImageContentFactory $imageContentFactory,
        State $state,
        MediaGalleryProcessor $mediaProcessor,
        ProductMetadataInterface   $productMetadata
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->productFactory = $productFactory;
        $this->eavSetup = $eavSetupFactory;
        $this->storeManager = $storeManager;
        $this->file = $file;
        $this->reader = $reader;
        $this->directoryList = $directoryList;
        $this->productRepository = $productRepository;
        $this->mediaGalleryEntryFactory = $mediaGalleryEntryFactory;
        $this->mediaGalleryManagement = $mediaGalleryManagement;
        $this->imageContentFactory = $imageContentFactory;
        $this->state = $state;
        $this->mediaProcessor = $mediaProcessor;
        $this->productMetadata = $productMetadata;
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
        //ADD WARRANTY PRODUCT TO THE DB
        $this->state->emulateAreaCode(Area::AREA_ADMINHTML, function () {
            $this->addImageToPubMedia();
            $this->createWarrantyProduct();
            $this->enablePriceForWarrantyProducts();
        });

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->state->emulateAreaCode(Area::AREA_ADMINHTML, function () {
            $this->deleteImageFromPubMedia();
        });

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Get image to pub media
     *
     * @return void
     *
     * @throws FileSystemException
     */
    public function addImageToPubMedia()
    {
        $imagePath = $this->reader->getModuleDir('', 'Extend_Warranty');
        $imagePath .= '/Setup/Resource/Extend_icon.png';

        $media = $this->getMediaImagePath();

        $this->file->cp($imagePath, $media);
    }

    /**
     * Delete image from pub/media
     *
     * @return void
     * @throws FileSystemException
     */
    public function deleteImageFromPubMedia()
    {
        $imageWarranty = $this->getMediaImagePath();
        $this->file->rm($imageWarranty);
    }

    /**
     * Process media gallery entry
     *
     * @param string $filePath
     * @param string $sku
     *
     * @return void
     *
     * @throws NoSuchEntityException
     * @throws StateException
     * @throws InputException
     */
    private function processMediaGalleryEntry($filePath, $sku)
    {
        $entry = $this->mediaGalleryEntryFactory->create();

        $entry->setFile($filePath);
        $entry->setMediaType('image');
        $entry->setDisabled(false);
        $entry->setTypes(['thumbnail', 'image', 'small_image']);

        $imageContent = $this->imageContentFactory->create();
        $imageContent
            ->setType(mime_content_type($filePath))
            ->setName('Extend Protection Plan')
            ->setBase64EncodedData(base64_encode($this->file->read($filePath)));

        $entry->setContent($imageContent);

        $this->processImages($sku,$entry);
    }

    /**
     *
     * Method to save Warranty images
     *
     * @param $sku
     * @param $entry
     * @return void
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    protected function processImages($sku,$entry){
        $product = $this->productRepository->get($sku, true, Store::DEFAULT_STORE_ID);

        // set all media types if not specified
        if ($entry->getTypes() == null) {
            $entry->setTypes(array_keys($product->getMediaAttributes()));
        }
        $mediaGalleryEntries = [$entry];

        /** using product model to map entity to array for future process gallery */
        $product->setMediaGalleryEntries($mediaGalleryEntries);

        $newImages = $product->getMediaGallery('images');
        $this->mediaProcessor->processMediaGallery($product,$newImages);
        $this->saveProduct($product);
    }

    /**
     * Get media image path
     *
     * @return string
     *
     * @throws FileSystemException
     */
    public function getMediaImagePath()
    {
        $path = $this->directoryList->getPath('media');
        $path .= '/Extend_icon.png';

        return $path;
    }

    /**
     * Get warranty attribute set
     *
     * @param Product $warranty
     *
     * @return int
     * @throws LocalizedException
     */
    public function getWarrantyAttributeSet($warranty)
    {
        /** @var Product $warranty */
        $default = $warranty->getDefaultAttributeSetId();

        if (!$default) {
            throw new LocalizedException(new Phrase('Unable to find default attribute set'));
        }

        return $default;
    }

    /**
     * Create warranty product
     *
     * @return void
     *
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function createWarrantyProduct()
    {
        $warranty = $this->productFactory->create();
        if (!$warranty->getIdBySku(self::WARRANTY_PRODUCT_SKU)) {
            $attributeSetId = $this->getWarrantyAttributeSet($warranty);
            $websites = $this->storeManager->getWebsites();

            $warranty->setSku(self::WARRANTY_PRODUCT_SKU)
                ->setName('Extend Protection Plan')
                ->setWebsiteIds(array_keys($websites))
                ->setAttributeSetId($attributeSetId)
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

            $this->saveProduct($warranty);
            $this->processMediaGalleryEntry($this->getMediaImagePath(), $warranty->getSku());
        }
    }

    /**
     * Enable price attribute available for warranty product type
     *
     * @return void
     */
    public function enablePriceForWarrantyProducts()
    {
        try {
            $this->state->getAreaCode();

        } catch (LocalizedException $e) {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        }

        $eavSetup = $this->eavSetup->create(['setup' => $this->moduleDataSetup]);
        //MAKE PRICE ATTRIBUTE AVAILABLE FOR WARRANTY PRODUCT TYPE
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

            //If apply_to attribute is empty or single value, use default Magento values
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

    /**
     * @param ProductInterface $product
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function saveProduct($product)
    {
        if (in_array(
            $this->productMetadata->getVersion(),
            self::MAGENTO_REPOSITORY_ISSUE_VERSIONS)
        ) {
            /**
             * using deprecated method instead of repository as magento 2.4.0
             * has issue with saving products attributes in multi stores
             * It rewrites product->data['store_id'] and saves attribute values
             * in substores instead of default store
             */
            $product->save();
        } else {
            $this->productRepository->save($product);
        }
    }
}
