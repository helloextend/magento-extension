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

class InstallData implements InstallDataInterface
{
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

    public function __construct(
        ProductFactory $productFactory,
        EavSetupFactory $eavSetupFactory,
        State $state,
        StoreManagerInterface $storeManager,
        File $file,
        Reader $reader,
        DirectoryList $directoryList,
        ProductRepositoryInterface $productRepository
    ) {
        $this->productFactory = $productFactory;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->state = $state;
        $this->storeManager = $storeManager;
        $this->file = $file;
        $this->reader = $reader;
        $this->directoryList = $directoryList;
        $this->productRepository = $productRepository;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (LocalizedException $e) {
            //Intentionally Left Empty
        }
        // $this->state->setAreaCode(Area::AREA_ADMINHTML);

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

    public function addImageToPubMedia()
    {
        $imagePath = $this->reader->getModuleDir(\Magento\Framework\Module\Dir::MODULE_VIEW_DIR, 'Extend_Warranty');
        $imagePath .= '/../Setup/Resource/Extend_icon.png';

        $media = $this->directoryList->getPath('media');
        $media .= '/Extend_icon.png';

        $this->file->cp($imagePath, $media);
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

        $warranty->setSku('WARRANTY-1')
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

        $imagePath = 'Extend_icon.png';
        $warranty->addImageToMediaGallery($imagePath, ['image', 'small_image', 'thumbnail'], false, false);

        $this->productRepository->save($warranty);
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
