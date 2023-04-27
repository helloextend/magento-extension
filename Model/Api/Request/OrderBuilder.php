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

class OrderBuilder
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
     * ContractBuilder constructor
     *
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param CountryInformationAcquirerInterface $countryInformationAcquirer
     * @param DataHelper $helper
     * @param ApiDataHelper $apiHelper
     * @param WarrantyRelation $warrantyRelation
     * @param ProductDataBuilder $productDataBuilder
     */
    public function __construct(
        StoreManagerInterface               $storeManager,
        ProductRepositoryInterface          $productRepository,
        CountryInformationAcquirerInterface $countryInformationAcquirer,
        DataHelper                          $helper,
        ApiDataHelper                       $apiHelper,
        WarrantyRelation                    $warrantyRelation,
        ProductDataBuilder                  $productDataBuilder
    )
    {
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->countryInformationAcquirer = $countryInformationAcquirer;
        $this->helper = $helper;
        $this->apiHelper = $apiHelper;
        $this->warrantyRelation = $warrantyRelation;
        $this->productDataBuilder = $productDataBuilder;
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
        OrderInterface     $order,
        OrderItemInterface $orderItem,
        int                $qty,
        string             $type = 'contract'
    ): array
    {
        $payload = [];
        $store = $this->storeManager->getStore($order->getStoreId());
        $currencyCode = $order->getOrderCurrencyCode();

        $extendStoreId = $this->apiHelper->getStoreId(ScopeInterface::SCOPE_STORES, $store->getId());
        $extendStoreName = $this->apiHelper->getStoreName(ScopeInterface::SCOPE_STORES, $store->getId());

        if (!$currencyCode) {
            $currencyCode = $store->getBaseCurrencyCode() ?? Currency::DEFAULT_CURRENCY;
        }

        $transactionTotal = $this->helper->formatPrice($order->getBaseGrandTotal());
        $lineItem = [];

        $discountAmount = $this->helper->formatPrice($orderItem->getBaseDiscountAmount());
        $taxCost = $this->helper->formatPrice($orderItem->getBaseTaxAmount());

        if ($type == \Extend\Warranty\Model\Orders::CONTRACT) {
            $productSku = $orderItem->getProductOptionByCode(Type::ASSOCIATED_PRODUCT);
            $productSku = is_array($productSku) ? array_shift($productSku) : $productSku;

            $plan = $this->getPlan($orderItem);

            $product = $this->prepareProductPayload($productSku);

            $lineItem = [
                'status' => $this->getStatus(),
                'product' => $product,
                'plan' => $plan,
                'discountAmount' => $discountAmount,
                'taxCost' => $taxCost,
                'quantity' => $qty
            ];

        } elseif ($type == \Extend\Warranty\Model\Orders::LEAD) {
            $productSku = $this->warrantyRelation->getOfferOrderItemSku($orderItem);
            $product = $this->prepareProductPayload($productSku);

            $lineItem = [
                'status' => $this->getStatus(),
                'discountAmount' => $discountAmount,
                'taxCost' => $taxCost,
                'product' => $product
            ];
        } elseif ($type == \Extend\Warranty\Model\Orders::LEAD_CONTRACT) {
            $plan = $this->getPlan($orderItem);
            $leadToken = $orderItem->getLeadToken() ?? '';

            if (!empty($leadToken)) {
                try {
                    $leadToken = implode(", ", $this->helper->unserialize($leadToken));
                } catch (Exception $exception) {
                    $leadToken = '';
                }
            }

            $lineItem = [
                'status' => $this->getStatus(),
                'quantity' => $qty,
                'plan' => $plan,
                'discountAmount' => $discountAmount,
                'taxCost' => $taxCost,
                'leadToken' => $leadToken
            ];
        }

        $lineItems[] = $lineItem;

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
            'productCostTotal' => $this->helper->formatPrice($order->getBaseSubtotal()),
            'discountAmountTotal' => $this->helper->formatPrice(abs($order->getBaseDiscountAmount())),
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

        $transactionTotal = $this->helper->formatPrice($order->getBaseGrandTotal());
        $lineItem = [];
        $lineItems = [];

        foreach ($order->getItems() as $orderItem) {

            $productSku = $this->warrantyRelation->getOfferOrderItemSku($orderItem);
            $qty = $orderItem->getQtyOrdered();

            if ($orderItem->getProductType() == Type::TYPE_CODE) {
                $product = $this->prepareWarrantyProductPayload($productSku, $orderItem->getPrice());
            } else {
                $product = $this->prepareProductPayload($productSku);
            }

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
     * Prepare product payload
     *
     * @param string|null $productSku
     * @return array
     */
    protected function prepareProductPayload(?string $productSku): array
    {
        if (empty($productSku)) {
            return [];
        }

        $product = $this->getProduct($productSku);

        if (!$product) {
            return [];
        }

        $productPayload = $this->productDataBuilder->preparePayload($product);

        $result = [
            'id' => $product->getSku(),
            'listPrice' => $this->helper->formatPrice(
                $this->productDataBuilder->calculateSyncProductPrice($product)
            ),
            'name' => $product->getName(),
            'purchasePrice' => $this->helper->formatPrice($product->getFinalPrice())
        ];

        $result = array_merge($result, $productPayload);
        return $result;
    }

    /**
     * Prepare warranty product payload
     *
     * @param string|null $productSku
     * @param float $price
     * @return array
     */
    protected function prepareWarrantyProductPayload(?string $productSku, float $price): array
    {
        if (empty($productSku)) {
            return [];
        }

        $product = $this->getProduct($productSku);

        if (!$product) {
            return [];
        }

        $result = [
            'id' => $product->getSku(),
            'listPrice' => $this->helper->formatPrice($price),
            'name' => $product->getName(),
            'purchasePrice' => $this->helper->formatPrice($price)
        ];

        return $result;
    }

    /**
     * Get plan
     *
     * @param OrderItemInterface $orderItem
     * @return array
     */
    protected function getPlan(OrderItemInterface $orderItem): array
    {
        $warrantyId = $orderItem->getProductOptionByCode(Type::WARRANTY_ID);
        $warrantyId = is_array($warrantyId) ? array_shift($warrantyId) : $warrantyId;

        $plan = [
            'purchasePrice' => $this->helper->formatPrice($orderItem->getPrice()),
            'id' => $warrantyId,
        ];

        return $plan;
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
     * Get product
     *
     * @param string $sku
     * @return ProductInterface|null
     */
    protected function getProduct(string $sku)
    {
        try {
            $product = $this->productRepository->get($sku);
        } catch (LocalizedException $e) {
            $product = null;
        }

        return $product;
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

    /**
     * Get Extend Order API status
     *
     * @return string
     */
    protected function getStatus(): string
    {
        return 'fulfilled';
    }
}
