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

use Extend\Warranty\Helper\Data as DataHelper;
use Extend\Warranty\Helper\Api\Data as ApiDataHelper;
use Extend\Warranty\Model\WarrantyRelation;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Locale\Currency;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Extend\Warranty\Model\Product\Type;
use Exception;

class FullOrderBuilder
{
    /**
     * Platform code
     */
    public const PLATFORM_CODE = 'magento';

    /**
     * Product Repository Interface
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Store Manager Interface
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Country Information Acquirer Interface
     *
     * @var CountryInformationAcquirerInterface
     */
    private $countryInformationAcquirer;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $helper;

    /**
     * Extend API helper
     *
     * @var ApiDataHelper
     */
    private $apiHelper;

    /**
     * @var WarrantyRelation
     */
    private $warrantyRelation;

    /**
     * @var ProductDataBuilder
     */
    protected $productDataBuilder;

    /**
     * @var LineItemBuilderFactory
     */
    protected $lineItemBuilderFactory;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param CountryInformationAcquirerInterface $countryInformationAcquirer
     * @param DataHelper $helper
     * @param ApiDataHelper $apiHelper
     * @param WarrantyRelation $warrantyRelation
     * @param ProductDataBuilder $productDataBuilder
     * @param LineItemBuilderFactory $lineItemBuilder
     */
    public function __construct(
        StoreManagerInterface               $storeManager,
        ProductRepositoryInterface          $productRepository,
        CountryInformationAcquirerInterface $countryInformationAcquirer,
        DataHelper                          $helper,
        ApiDataHelper                       $apiHelper,
        WarrantyRelation                    $warrantyRelation,
        ProductDataBuilder                  $productDataBuilder,
        LineItemBuilderFactory              $lineItemBuilder
    )
    {
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->countryInformationAcquirer = $countryInformationAcquirer;
        $this->helper = $helper;
        $this->apiHelper = $apiHelper;
        $this->warrantyRelation = $warrantyRelation;
        $this->productDataBuilder = $productDataBuilder;
        $this->lineItemBuilderFactory = $lineItemBuilder;
    }

    /**
     * Prepare payload
     *
     * @param OrderInterface $order
     * @param OrderItemInterface $orderItem
     * @param int $qty
     * @param string $type
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function preparePayload(
        OrderInterface $order
    ): array
    {
        $storeId = $order->getStoreId();

        $extendStoreId = $this->apiHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId);
        $extendStoreName = $this->apiHelper->getStoreName(ScopeInterface::SCOPE_STORES, $storeId);

        $store = $this->storeManager->getStore($storeId);
        $currencyCode = $order->getOrderCurrencyCode() ?: $store->getBaseCurrencyCode() ?? Currency::DEFAULT_CURRENCY;

        $transactionTotal = $this->helper->formatPrice($order->getGrandTotal());

        foreach ($order->getItems() as $orderItem) {
            /** @var LineItemBuilder $lineItemBuilder */
            $lineItemBuilder = $this->lineItemBuilderFactory->create(['item' => $orderItem]);
            $lineItem = $lineItemBuilder->preparePayload();
            $lineItems[] = $lineItem;
        }

        if (empty($lineItems)) {
            return [];
        }

        $saleOrigin = [
            'platform' => self::PLATFORM_CODE,
        ];

        $createdAt = $order->getCreatedAt();
        $customerData = $this->getCustomerData($order);

        if (empty($customerData)) {
            return [];
        }

        $payload = [
            'isTest' => !$this->apiHelper->isExtendLive(ScopeInterface::SCOPE_STORES, $storeId),
            'currency' => $currencyCode,
            'createdAt' => $createdAt ? strtotime($createdAt) : 0,
            'customer' => $customerData,
            'lineItems' => $lineItems,
            'total' => $transactionTotal,
            'taxCostTotal' => $this->helper->formatPrice($order->getTaxAmount()),
            'productCostTotal' => $this->helper->formatPrice($order->getSubtotal()),
            'discountAmountTotal' => $this->helper->formatPrice(abs($order->getDiscountAmount())),
            'storeId' => $extendStoreId,
            'storeName' => $extendStoreName,
            'transactionId' => $order->getIncrementId(),
            'saleOrigin' => $saleOrigin,
        ];

        return $payload;
    }

    public function prepareHistoricalOrdersPayLoad(OrderInterface $order): array
    {
        $payload = [];
        $store = $this->storeManager->getStore($order->getStoreId());
        $currencyCode = $order->getOrderCurrencyCode();

        if (!$currencyCode) {
            $currencyCode = $store->getBaseCurrencyCode() ?? Currency::DEFAULT_CURRENCY;
        }
        $extendStoreId = $this->apiHelper->getStoreId(ScopeInterface::SCOPE_STORES, $store->getId());
        $extendStoreName = $this->apiHelper->getStoreName(ScopeInterface::SCOPE_STORES, $store->getId());

        $transactionTotal = $this->helper->formatPrice($order->getGrandTotal());
        $lineItems = [];

        foreach ($order->getItems() as $orderItem) {

            $productSku = $this->warrantyRelation->getOfferOrderItemSku($orderItem);
            $qty = $orderItem->getQtyOrdered();

            if ($orderItem->getProductType() == Type::TYPE_CODE) {
                $product = $this->prepareWarrantyProductPayload($productSku, $orderItem->getPrice());
            } else {
                $product = $this->prepareProductPayload($productSku);
            }

            $product['purchasePrice'] = $this->helper->formatPrice($orderItem->getRowTotal() / $qty);

            if (empty($product)) {
                continue;
            }

            $lineItem = [
                'quantity' => $qty,
                'storeId' => $extendStoreId,
                'product' => $product,
            ];

            $lineItems[] = $lineItem;
        }

        if (empty($lineItems)) {
            return $payload;
        }

        $saleOrigin = [
            'platform' => self::PLATFORM_CODE,
        ];

        $createdAt = $order->getCreatedAt();
        $customerData = $this->getCustomerData($order);

        if (empty($customerData)) {
            return $payload;
        }

        $payload = [
            'isTest' => !$this->apiHelper->isExtendLive(ScopeInterface::SCOPE_STORES, $store->getId()),
            'currency' => $currencyCode,
            'createdAt' => $createdAt ? strtotime($createdAt) : 0,
            'customer' => $customerData,
            'lineItems' => $lineItems,
            'total' => $transactionTotal,
            'storeId' => $extendStoreId,
            'storeName' => $extendStoreName,
            'transactionId' => $order->getIncrementId(),
            'saleOrigin' => $saleOrigin,
        ];

        return $payload;
    }

    /**
     * Format street
     *
     * @param array $street
     * @return array
     */
    protected function formatStreet(array $street = []): array
    {
        $address = [];

        $address['address1'] = array_shift($street);
        if (!empty($street)) {
            $address['address2'] = implode(",", $street);
        }

        return $address;
    }


    /**
     * Prepare customer data
     *
     * @param OrderInterface $order
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getCustomerData(OrderInterface $order): array
    {
        $customer = [];
        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            $billingCountryId = $billingAddress->getCountryId();
            $billingCountryInfo = $this->countryInformationAcquirer->getCountryInfo($billingCountryId);
            $billingStreet = $this->formatStreet($billingAddress->getStreet());

            $customer = [
                'name' => $this->helper->getCustomerFullName($order),
                'email' => $order->getCustomerEmail(),
                'phone' => $billingAddress->getTelephone(),
                'billingAddress' => [
                    'address1' => $billingStreet['address1'] ?? '',
                    'address2' => $billingStreet['address2'] ?? '',
                    'city' => $billingAddress->getCity(),
                    'countryCode' => $billingCountryInfo->getThreeLetterAbbreviation(),
                    'postalCode' => $billingAddress->getPostcode(),
                    'province' => $billingAddress->getRegionCode() ?? ''
                ],
            ];
        }

        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $shippingCountryId = $shippingAddress->getCountryId();
            $shippingCountryInfo = $this->countryInformationAcquirer->getCountryInfo($shippingCountryId);
            $shippingStreet = $this->formatStreet($shippingAddress->getStreet());

            $customer['shippingAddress'] = [
                'address1' => $shippingStreet['address1'] ?? '',
                'address2' => $shippingStreet['address2'] ?? '',
                'city' => $shippingAddress->getCity(),
                'countryCode' => $shippingCountryInfo->getThreeLetterAbbreviation(),
                'postalCode' => $shippingAddress->getPostcode(),
            ];
        }

        return $customer;
    }
}
