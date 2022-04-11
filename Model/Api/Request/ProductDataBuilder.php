<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Request;

use Extend\Warranty\Helper\Data as Helper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Media\ConfigInterface as ProductMediaConfig;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\Currency;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ProductDataBuilder
 */
class ProductDataBuilder
{
    /**
     * Delimiter in category path.
     */
    const DELIMITER_CATEGORY = '/';

    /**
     * Configuration identifier
     */
    const CONFIGURATION_IDENTIFIER = 'configurableChild';

    /**
     * Category Repository Interface
     *
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * Helper
     *
     * @var Helper
     */
    private $helper;

    /**
     * Product Media Config
     *
     * @var ProductMediaConfig
     */
    private $configMedia;

    /**
     * Store Manager Interface
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Product Resource Model
     *
     * @var ProductResourceModel
     */
    private $productResourceModel;

    /**
     * Option Provider
     *
     * @var OptionProvider
     */
    private $optionProvider;

    /**
     * ProductDataBuilder constructor
     *
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductMediaConfig $configMedia
     * @param Helper $helper
     * @param ProductResourceModel $productResourceModel
     * @param OptionProvider $optionProvider
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        ProductMediaConfig $configMedia,
        Helper $helper,
        ProductResourceModel $productResourceModel,
        OptionProvider $optionProvider,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->configMedia = $configMedia;
        $this->helper = $helper;
        $this->productResourceModel = $productResourceModel;
        $this->optionProvider = $optionProvider;
        $this->storeManager = $storeManager;
    }

    /**
     * Prepare payload
     *
     * @param ProductInterface $product
     * @return array
     */
    public function preparePayload(ProductInterface $product): array
    {
        $categories = $this->getCategories($product);

        $storeId = (int)$product->getStoreId();
        $currencyCode = $this->getCurrencyCode($storeId);

        $price = [
            'amount'        => $this->helper->formatPrice($product->getPrice()),
            'currencyCode'  => $currencyCode,
        ];

        $identifiers = [
            'sku'   => (string)$product->getSku(),
            'type'  => (string)$product->getTypeId(),
        ];

        $payload = [
            'category'          => $categories,
            'description'       => (string)$product->getShortDescription() ?? __('No description'),
            'price'             => $price,
            'title'             => (string)$product->getName(),
            'referenceId'       => (string)$product->getSku(),
            'identifiers'       => $identifiers,
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
