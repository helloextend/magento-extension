<?php


namespace Extend\Warranty\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Model\ProductFactory;
use Extend\Warranty\Model\Product\Type;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\Product\Gallery\EntryFactory;
use Magento\Catalog\Model\Product\Gallery\GalleryManagement;
use Magento\Framework\Api\ImageContentFactory;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\FileSystemException;

class InstallData implements InstallDataInterface
{
    /**
     * warranty product sku
     */
    const WARRANTY_PRODUCT_SKU = 'WARRANTY-1';

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @var State
     */
    protected $state;

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


    public function __construct
    (
        ProductFactory $productFactory,
        EavSetupFactory $eavSetupFactory,
        State $state,
        StoreManagerInterface $storeManager,
        File $file,
        Reader $reader,
        DirectoryList $directoryList,
        ProductRepositoryInterface $productRepository,
        EntryFactory $mediaGalleryEntryFactory,
        GalleryManagement $mediaGalleryManagement,
        ImageContentFactory $imageContentFactory
    )
    {
        $this->productFactory = $productFactory;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->state = $state;
        $this->storeManager = $storeManager;
        $this->file = $file;
        $this->reader = $reader;
        $this->directoryList = $directoryList;
        $this->productRepository = $productRepository;
        $this->mediaGalleryEntryFactory = $mediaGalleryEntryFactory;
        $this->mediaGalleryManagement = $mediaGalleryManagement;
        $this->imageContentFactory = $imageContentFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (LocalizedException $e) {
            //Intentionally Left Empty
        }

        $setup->startSetup();
        $eavSetup = $this->eavSetupFactory->create();

        //ADD WARRANTY PRODUCT TO THE DB
        $this->addImageToPubMedia();
        $this->createWarrantyProduct();

        /** Attribute not being used **/
        // $this->addSyncAttribute($eavSetup);

        $this->enablePriceForWarrantyProducts($eavSetup);

        $setup->endSetup();
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
     * Process media gallery entry
     *
     * @param $filePath
     * @param $sku
     *
     * @return void
     *
     * @throws NoSuchEntityException
     * @throws StateException
     * @throws InputException
     */
    public function processMediaGalleryEntry($filePath, $sku)
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
            ->setBase64EncodedData(base64_encode(file_get_contents($filePath)));

        $entry->setContent($imageContent);

        $this->mediaGalleryManagement->create($sku, $entry);
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


    public function getWarrantyAttributeSet($warranty)
    {
        /** @var Product $warranty */
        $default = $warranty->getDefaultAttributeSetId();

        if (!$default) {
            throw new \Exception('Unable to find default attribute set');
        }

        return $default;
    }

    public function createWarrantyProduct()
    {
        $warranty = $this->productFactory->create();
        $attributeSetId = $this->getWarrantyAttributeSet($warranty);
        $websites = $this->storeManager->getWebsites();

        $warranty->setSku(self::WARRANTY_PRODUCT_SKU)
            ->setName('Extend Protection Plan')
            ->setWebsiteIds(array_keys($websites))
            ->setAttributeSetId($attributeSetId)
            ->setStatus(Status::STATUS_ENABLED)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
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

        $imagePath = $this->getMediaImagePath();

        $this->productRepository->save($warranty);

        $this->processMediaGalleryEntry($imagePath, self::WARRANTY_PRODUCT_SKU);
    }

    public function addSyncAttribute($eavSetup)
    {
        //ADD SYNCED DATE ATTRIBUTE FOR PRODUCTS
        $eavSetup->addAttribute(
            Product::ENTITY,
            'extend_sync',
            [
                'type' => 'int',
                'label' => 'Synced with Extend',
                'input' => 'boolean',
                'required' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'visible' => false,
                'apply_to' => 'simple,virtual,configurable,downloadable,bundle'
            ]
        );
    }

    public function enablePriceForWarrantyProducts($eavSetup)
    {
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
}