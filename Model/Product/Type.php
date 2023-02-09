<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Catalog\Model\Product;
use Extend\Warranty\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Type
 *
 * Warranty Product Type Model
 */
class Type extends AbstractType
{
    /**
     * Product type code
     */
    public const TYPE_CODE = 'warranty';

    /**
     * Custom option codes
     */
    public const WARRANTY_ID = 'warranty_id';
    public const ASSOCIATED_PRODUCT = 'associated_product';
    public const ASSOCIATED_PRODUCT_NAME = 'associated_product_name';
    public const TERM = 'warranty_term';
    public const PLAN_TYPE = 'plan_type';
    public const BUY_REQUEST = 'info_buyRequest';
    public const SECONDARY_SKU = 'secondary_sku';

    /**
     * Custom option labels
     */
    public const ASSOCIATED_PRODUCT_LABEL = 'SKU';

    public const ASSOCIATED_PRODUCT_NAME_LABEL = 'Name';
    public const TERM_LABEL = 'Term';

    /**
     * Warranty Helper
     *
     * @var Data
     */
    protected $helper;

    /**
     * Type constructor.
     *
     * @param Product\Option $catalogProductOption
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param Product\Type $catalogProductType
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Psr\Log\LoggerInterface $logger
     * @param ProductRepositoryInterface $productRepository
     * @param Data $helper
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     */
    public function __construct(
        \Magento\Catalog\Model\Product\Option              $catalogProductOption,
        \Magento\Eav\Model\Config                          $eavConfig,
        \Magento\Catalog\Model\Product\Type                $catalogProductType,
        \Magento\Framework\Event\ManagerInterface          $eventManager,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb,
        \Magento\Framework\Filesystem                      $filesystem,
        \Magento\Framework\Registry                        $coreRegistry,
        \Psr\Log\LoggerInterface                           $logger,
        ProductRepositoryInterface                         $productRepository,
        Data                                               $helper,
        \Magento\Framework\Serialize\Serializer\Json       $serializer = null
    ) {
        $this->helper = $helper;
        parent::__construct(
            $catalogProductOption,
            $eavConfig,
            $catalogProductType,
            $eventManager,
            $fileStorageDb,
            $filesystem,
            $coreRegistry,
            $logger,
            $productRepository,
            $serializer
        );
    }

    /**
     * Delete type specific data
     *
     * @param Product $product
     * @return void
     */
    public function deleteTypeSpecificData(Product $product)
    {
        return null;
    }

    /**
     * Is virtual
     *
     * @param Product $product
     * @return bool
     */
    public function isVirtual($product)
    {
        return true;
    }

    /**
     * Has weight
     *
     * @return bool
     */
    public function hasWeight()
    {
        return false;
    }

    /**
     * Prepare product
     *
     * @param \Magento\Framework\DataObject $buyRequest
     * @param Product $product
     * @param string $processMode
     * @return array|Product|Product[]|string
     * @throws LocalizedException
     */
    protected function _prepareProduct(\Magento\Framework\DataObject $buyRequest, $product, $processMode)
    {
        if(!$buyRequest->getProduct()){
            $this->_logger->error(sprintf(__('BuyRequest is not valid, product option is not provided.')));
            throw new LocalizedException(__('Warranty was unable to be added. Please try again.'));
        }

        $price = $this->helper->removeFormatPrice($buyRequest->getPrice());

        $buyRequest->setData('custom_price', $price);

        try {
            $relatedProduct = $this->productRepository->get($buyRequest->getProduct());
            $product->addCustomOption(self::ASSOCIATED_PRODUCT_NAME, $relatedProduct->getName());
        } catch (NoSuchEntityException $e) {
            $this->_logger->error(sprintf(__('Warrantable Product not found. Sku: %s'), $buyRequest->getProduct()));
        }
        $product->addCustomOption(self::WARRANTY_ID, $buyRequest->getData('planId'));
        $product->addCustomOption(self::ASSOCIATED_PRODUCT, $buyRequest->getProduct());
        $product->addCustomOption(self::TERM, $buyRequest->getTerm());
        $product->addCustomOption(self::PLAN_TYPE, $buyRequest->getData('coverageType'));
        $product->addCustomOption(self::BUY_REQUEST, $this->serializer->serialize($buyRequest->getData()));

        if($buyRequest->hasSecondarySku()){
            $product->addCustomOption(self::SECONDARY_SKU, $buyRequest->getSecondarySku());
        }

        if ($this->_isStrictProcessMode($processMode)) {
            $product->setCartQty($buyRequest->getQty());
        }
        $product->setQty($buyRequest->getQty());

        return $product;
    }

    /**
     * Get order options
     *
     * @param Product $product
     * @return array
     */
    public function getOrderOptions($product)
    {
        $options = parent::getOrderOptions($product);

        if ($warrantyId = $product->getCustomOption(self::WARRANTY_ID)) {
            $options[self::WARRANTY_ID] = $warrantyId->getValue();
        }

        if ($associatedProduct = $product->getCustomOption(self::ASSOCIATED_PRODUCT)) {
            $options[self::ASSOCIATED_PRODUCT] = $associatedProduct->getValue();
        }

        if ($secondarySkuOption = $product->getCustomOption(self::SECONDARY_SKU)) {
            $options[self::SECONDARY_SKU] = $secondarySkuOption->getValue();
        }

        if ($term = $product->getCustomOption(self::TERM)) {
            $options[self::TERM] = $term->getValue();
        }

        if ($planType = $product->getCustomOption(self::PLAN_TYPE)) {
            $options[self::PLAN_TYPE] = $planType->getValue();
        }

        return $options;
    }
}
