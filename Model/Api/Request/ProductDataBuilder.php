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
use Exception;
use Magento\Bundle\Model\Product\Price as BundlePrice;
use Psr\Log\LoggerInterface;

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
     * @var BundlePrice
     */
    private $bundlePrice;

    /**
     * Logger Model
     *
     * @var LoggerInterface
     */
    private $logger;

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
     * @param BundlePrice $bundlePrice
     * @param LoggerInterface $logger
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        ProductMediaConfig          $configMedia,
        Helper                      $helper,
        ApiHelper                   $apiHelper,
        ProductResourceModel        $productResourceModel,
        OptionProvider              $optionProvider,
        StoreManagerInterface       $storeManager,
        Type                        $catalogProductType,
        BundlePrice                 $bundlePrice,
        LoggerInterface             $logger
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
        $this->bundlePrice = $bundlePrice;
        $this->logger = $logger;
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
            'category' => $categories ?: self::NO_CATEGORY_DEFAULT_VALUE,
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
     * @return float|int|null
     */
    public function calculateSyncProductPrice(
        ProductInterface $product,
                         $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                         $scopeId = null
    ): float|int|null
    {
        $specialPricesEnabled   = $this->_getIsSpecialPricesSyncEnabled($scopeType, $scopeId);

        // retrieve price logic differs for bundled items

        if ($product->getTypeId() !== 'bundle') {
            $price          = $product->getPrice();
            $specialPrice   = $this->catalogProductType->priceFactory($product->getTypeId())->getFinalPrice(1, $product);

            if ($specialPricesEnabled && (float)$specialPrice < (float)$price) {
                $price      = $specialPrice;
            }
            return $price;
        }else{
            // bundled products
            try {
                $bundleTotalPrice = 0;
                $bundlePriceCalc = $this->bundlePrice->getTotalBundleItemsPrice($product);
                $bundleOptions = $product->getTypeInstance()->getOptionsCollection($product);

                // Get selection collection (the products associated with the bundle)
                $selections = $product->getTypeInstance()->getSelectionsCollection(
                    $bundleOptions->getAllIds(),
                    $product
                )->addAttributeToSelect('is_default'); // Include 'is_default' attribute

                // Group selections by option ID
                $selectionsByOption = [];
                foreach ($selections as $selection) {
                    $selectionsByOption[$selection->getOptionId()][] = $selection;
                }

                // Iterate over each option and sum the prices of the default selections
                foreach ($bundleOptions as $option) {
                    if (isset($selectionsByOption[$option->getId()])) {
                        foreach ($selectionsByOption[$option->getId()] as $selection) {
                            if ($selection->getData('is_default')) {
                                $bundleTotalPrice += $selection->getFinalPrice();
                            }
                        }
                    }
                }

                if ($bundlePriceCalc == 0 && $bundleTotalPrice == 0) {
                    return 0.01;
                }

                // Return the lowest non-zero value
                if ($bundlePriceCalc == 0) {
                    return $bundleTotalPrice;
                }
                if ($bundleTotalPrice == 0) {
                    return $bundlePriceCalc;
                }

                return min($bundlePriceCalc, $bundleTotalPrice);

            } catch (\Exception $exception){

                $this->logger->error('Could not retrieve bundle prices because of the following error: ');
                $this->logger->error($exception->getMessage());

                return 0.01;
            }
        }
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
                        $names[] = $this->sanitizeCategoryName($parentCategories[$id]->getName());
                    }
                }
                $categories[] = implode(self::DELIMITER_CATEGORY, $names);
            }
        }

        return implode(',', $categories);
    }

    /**
     * Returns sanitized value for payload
     *
     * @param string|null $theString
     * @return string|null
     */
    private function sanitizeCategoryName(?string $theString): ?string
    {
        if (!$theString)
            return null;

        // Use a regular expression to find HTML-encoded sections (e.g., %25)
        $encodedSectionRegex = '/%[0-9A-Fa-f]{2}/';

        // Decode HTML-encoded values using a callback function
        $decodedString = preg_replace_callback($encodedSectionRegex, function($match) {
            return urldecode($match[0]);
        }, $theString);

        // Replace remaining breaking characters
        $resultString = str_replace('%', 'pct ', $decodedString);
        $resultString = str_replace('?', '.', $resultString);
        $resultString = str_replace('#', '.', $resultString);
        $resultString = str_replace('&', 'and ', $resultString);

        return $resultString;
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
