<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Request;

use Extend\Warranty\Helper\Data as Helper;
use Extend\Warranty\Helper\Api\Data as ApiHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Media\ConfigInterface as ProductMediaConfig;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\Currency;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Type;

/**
 * Class ProductDataBuilder
 *
 * Warranty ProductDataBuilder
 */
class ProductDataBuilder
{
    /**
     * Delimiter in category path.
     */
    public const DELIMITER_CATEGORY = '/';

    public const NO_CATEGORY_DEFAULT_VALUE = 'No Category';

    /**
     * Configuration identifier
     */
    public const CONFIGURATION_IDENTIFIER = 'configurableChild';

    /**
     * Category Repository Interface
     *
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * Warranty Helper
     *
     * @var Helper
     */
    private $helper;

    /**
     * Warranty Api Helper
     *
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * Product Media Config Model
     *
     * @var ProductMediaConfig
     */
    private $configMedia;

    /**
     * Store Manager Model
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Product Resource
     *
     * @var ProductResourceModel
     */
    private $productResourceModel;

    /**
     * Option Provider Model
     *
     * @var OptionProvider
     */
    private $optionProvider;

    /**
     * Catalog product type
     *
     * @var Type
     */
    protected $catalogProductType;

    /**
     * @var array
     */
    private $_isSpecialPriceSyncEnabled = [];

    /**
     * ProductDataBuilder constructor
     *
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductMediaConfig $configMedia
     * @param Helper $helper
     * @param ApiHelper $apiHelper
     * @param ProductResourceModel $productResourceModel
     * @param OptionProvider $optionProvider
     * @param StoreManagerInterface $storeManager
     * @param Type $catalogProductType
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        ProductMediaConfig          $configMedia,
        Helper                      $helper,
        ApiHelper                   $apiHelper,
        ProductResourceModel        $productResourceModel,
        OptionProvider              $optionProvider,
        StoreManagerInterface       $storeManager,
        Type                        $catalogProductType
    )
    {
        $this->categoryRepository = $categoryRepository;
        $this->configMedia = $configMedia;
        $this->helper = $helper;
        $this->apiHelper = $apiHelper;
        $this->productResourceModel = $productResourceModel;
        $this->optionProvider = $optionProvider;
        $this->storeManager = $storeManager;
        $this->catalogProductType = $catalogProductType;
    }

    /**
     * Prepare payload
     *
     * @param ProductInterface $product
     * @return array
     */
    public function preparePayload(
        ProductInterface $product,
                         $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                         $scopeId = null
    ): array
    {
        $categories = $this->getCategories($product);

        $storeId = (int)$product->getStoreId();
        $currencyCode = $this->getCurrencyCode($storeId);

        $price = [
            'amount' => $this->helper->formatPrice($this->calculateSyncProductPrice($product, $scopeType, $scopeId)),
            'currencyCode' => $currencyCode,
        ];

        $identifiers = [
            'sku' => (string)$product->getSku(),
            'type' => (string)$product->getTypeId(),
        ];

        $description = trim((string)$product->getShortDescription());

        if (strlen($description) > 2000) {
            $description = substr($description, 0, 2000);
        }

        if (!$description) {
            $description = __('No description');
        }

        $payload = [
            'category' => $categories ? : self::NO_CATEGORY_DEFAULT_VALUE,
            'description' => $description,
            'price' => $price,
            'title' => (string)$product->getName(),
            'referenceId' => (string)$product->getSku(),
            'identifiers' => $identifiers,
        ];

        $imageUrl = $this->getProductImageUrl($product);
        if ($imageUrl) {
            $payload['imageUrl'] = $imageUrl;
        }

        $productId = (int)$product->getId();
        $parentProductSku = $this->getParentSkuByChild($productId);
        if ($parentProductSku) {
            $payload['parentReferenceId'] = $parentProductSku;
            $payload['identifiers']['parentSku'] = $parentProductSku;
            $payload['identifiers']['type'] = self::CONFIGURATION_IDENTIFIER;
        }

        return $payload;
    }

    /**
     * Calculates price with checking if special price should be used
     * for syncing
     *
     * @param ProductInterface $product
     * @param $scopeType
     * @param $scopeId
     * @return float|null
     */
    public function calculateSyncProductPrice(
        ProductInterface $product,
                         $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                         $scopeId = null
    ) {
        $price = $product->getPrice();
        $specialPricesEnabled = $this->_getIsSpecialPricesSyncEnabled($scopeType, $scopeId);
        $specialPrice = $this->catalogProductType->priceFactory($product->getTypeId())->getFinalPrice(1, $product);
        if ($specialPricesEnabled && (float)$specialPrice < (float)$price) {
            $price = $specialPrice;
        }

        return $price;
    }

    /**
     * @return bool
     */
    private function _getIsSpecialPricesSyncEnabled($scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = null): bool
    {
        if ($this->_isSpecialPriceSyncEnabled && isset($this->_isSpecialPriceSyncEnabled[$scopeType][$scopeId ?? Store::DEFAULT_STORE_ID])) {
            return $this->_isSpecialPriceSyncEnabled[$scopeType][$scopeId ?? Store::DEFAULT_STORE_ID];
        }

        $isEnabled = (bool)$this->apiHelper->isProductSpecialPriceSyncEnabled($scopeType, $scopeId);
        $this->_isSpecialPriceSyncEnabled[$scopeType][$scopeId ?? Store::DEFAULT_STORE_ID] = $isEnabled;

        return $isEnabled;
    }

    /**
     * Get categories
     *
     * @param ProductInterface $product
     * @return string
     */
    private function getCategories(ProductInterface $product): string
    {
        $categories = [];
        $categoryIds = $product->getCategoryIds();
        foreach ($categoryIds as $categoryId) {
            try {
                $category = $this->categoryRepository->get((int)$categoryId);
            } catch (NoSuchEntityException $exception) {
                $category = null;
            }

            if ($category) {
                $pathInStore = $category->getPathInStore();
                $pathIds = array_reverse(explode(',', $pathInStore));

                $parentCategories = $category->getParentCategories();

                $names = [];
                foreach ($pathIds as $id) {
                    if (isset($parentCategories[$id]) && $parentCategories[$id]->getName()) {
                        $names[] = $parentCategories[$id]->getName();
                    }
                }
                $categories[] = implode(self::DELIMITER_CATEGORY, $names);
            }
        }

        return implode(',', $categories);
    }

    /**
     * Get product image url
     *
     * @param ProductInterface $product
     * @return string
     */
    private function getProductImageUrl(ProductInterface $product): string
    {
        $imageUrl = '';
        $image = $product->getImage();
        if (!empty($image)) {
            $imageUrl = $this->configMedia->getBaseMediaUrl() . $image;
        }

        return $imageUrl;
    }

    /**
     * Get parent product sku by child id
     *
     * @param int $childId
     * @return string
     */
    private function getParentSkuByChild(int $childId): string
    {
        $connection = $this->productResourceModel->getConnection();
        $select = $connection->select();
        $select->from(['cpsl' => $connection->getTableName('catalog_product_super_link')], []);
        $select->join(
            ['cpe' => $connection->getTableName('catalog_product_entity')],
            'cpe.' . $this->optionProvider->getProductEntityLinkField() . ' = cpsl.parent_id',
            ['cpe.sku']
        );
        $select->where('cpsl.product_id=?', $childId);

        return (string)$connection->fetchOne($select);
    }

    /**
     * Get currency code
     *
     * @param int $storeId
     * @return string
     */
    private function getCurrencyCode(int $storeId): string
    {
        try {
            $store = $this->storeManager->getStore($storeId);
            $currentCurrency = $store->getCurrentCurrency();
            $currentCurrencyCode = $currentCurrency->getCode();
        } catch (LocalizedException $exception) {
            $currentCurrencyCode = Currency::DEFAULT_CURRENCY;
        }

        return $currentCurrencyCode;
    }
}
