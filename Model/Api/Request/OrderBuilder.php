<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

declare(strict_types=1);

namespace Extend\Warranty\Model\Api\Request;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\Locale\Currency;
use Extend\Warranty\Model\Orders as ExtendOrder;
use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Helper\Data as DataHelper;
use Extend\Warranty\Helper\Api\Data as ApiDataHelper;
use Extend\Warranty\Model\Offers;

class OrderBuilder
{
    /**
     * Platform code
     */
    const PLATFORM_CODE = 'magento';

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
     * @var Offers
     */
    private $offers;

    /**
     * Extend Orders API Builder constructor
     *
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param CountryInformationAcquirerInterface $countryInformationAcquirer
     * @param DataHelper $helper
     * @param ApiDataHelper $apiHelper
     * @param Offers $offers
     */
    public function __construct (
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        CountryInformationAcquirerInterface $countryInformationAcquirer,
        DataHelper $helper,
        ApiDataHelper $apiHelper,
        Offers $offers
    ) {
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->countryInformationAcquirer = $countryInformationAcquirer;
        $this->helper = $helper;
        $this->apiHelper = $apiHelper;
        $this->offers = $offers;
    }

    /**
     * Prepare payload
     *
     * @param OrderInterface $order
     * @param OrderItemInterface $orderItem
     * @param int $qty
     * @return array
     * @throws NoSuchEntityException
     */
    public function preparePayload(OrderInterface $order, OrderItemInterface $orderItem, int $qty, $type = 'contract'): array
    {
        $currencyCode = $order->getOrderCurrencyCode() ?? Currency::DEFAULT_CURRENCY;
        $transactionTotal = $this->helper->formatPrice($order->getBaseGrandTotal());

        $lineItem = $this->prepareLineItem($orderItem, $qty, $type);

        $lineItems[] = $lineItem;

        $saleOrigin = [
            'platform'  => self::PLATFORM_CODE,
        ];

        $payload = [
            'isTest'            => !$this->apiHelper->isExtendLive(),
            'currency'          => $currencyCode,
            'createdAt'         => strtotime($order->getCreatedAt()),
            'customer'          => $this->getCustomerData($order),
            'lineItems'         => $lineItems,
            'total'             => $transactionTotal,
            'storeId'           => $this->apiHelper->getStoreId(),
            'storeName'         => $this->apiHelper->getStoreName(),
            'transactionId'     => $order->getIncrementId(),
            'saleOrigin'        => $saleOrigin,
        ];

        return $payload;
    }

    public function preparePayloadBatch($orders): array
    {
        $BatchPayload = [];
        $payload = [];

        foreach ($orders as $order) {
            $lineItems = [];
            $currencyCode = $order->getOrderCurrencyCode() ?? Currency::DEFAULT_CURRENCY;
            $transactionTotal = $this->helper->formatPrice($order->getBaseGrandTotal());

            foreach ($order->getAllItems() as $orderItem) {
                $lineItem = $this->prepareLineItem($orderItem, (int)$orderItem->getQtyInvoiced(), ExtendOrder::BATCH);
                $lineItems[] = $lineItem;
            }

            $saleOrigin = [
                'platform'  => self::PLATFORM_CODE,
            ];

            $payload = [
                'isTest'            => !$this->apiHelper->isExtendLive(),
                'currency'          => $currencyCode,
                'createdAt'         => strtotime($order->getCreatedAt()),
                'customer'          => $this->getCustomerData($order),
                'lineItems'         => $lineItems,
                'total'             => $transactionTotal,
                'storeId'           => $this->apiHelper->getStoreId(),
                'storeName'         => $this->apiHelper->getStoreName(),
                'transactionId'     => $order->getIncrementId(),
                'saleOrigin'        => $saleOrigin,
            ];

            $BatchPayload[] = $payload;
        }

        return $BatchPayload;
    }

    protected function prepareLineItem(OrderItemInterface $orderItem, int $qty, string $type = 'contract'): array
    {
        $lineItem = [];
        $storeId = $orderItem->getStoreId();

        switch ($type) {
            case ExtendOrder::CONTRACT:
                $productSku = $orderItem->getProductOptionByCode(Type::ASSOCIATED_PRODUCT);
                $productSku = is_array($productSku) ? array_shift($productSku) : $productSku;
                $plan = $this->getPlan($orderItem);
                $product = $this->prepareProductPayload($productSku);

                $lineItem = [
                    'status'      => $this->getStatus(),
                    'quantity'    => $qty,
                    'storeId'     => $this->apiHelper->getStoreId(ScopeInterface::SCOPE_STORES,$storeId),
                    'warrantable' => true,
                    'product'     => $product,
                    'plan'        => $plan
                ];
                break;
            case $type == ExtendOrder::LEAD:
                $productSku = $orderItem->getSku();
                $product = $this->prepareProductPayload($productSku);

                $lineItem = [
                    'status'      => $this->getStatus(),
                    'quantity'    => $qty,
                    'storeId'     => $this->apiHelper->getStoreId(ScopeInterface::SCOPE_STORES,$storeId),
                    'warrantable' => true,
                    'product'     => $product
                ];
                break;
            case ExtendOrder::LEAD_CONTRACT:
                $plan = $this->getPlan($orderItem);
                $leadToken = '';

                if ($orderItem->getLeadToken() != null) {
                    $leadToken = implode(", ", json_decode($orderItem->getLeadToken(), true));
                }

                $lineItem = [
                    'status'      => $this->getStatus(),
                    'quantity'    => $qty,
                    'storeId'     => $this->apiHelper->getStoreId(),
                    'warrantable' => true,
                    'plan'        => $plan,
                    'leadToken'   => $leadToken
                ];
                break;
            case ExtendOrder::BATCH:
                $productSku = $orderItem->getSku();
                $product = $this->prepareProductPayload($productSku);

                $lineItem = [
                    'quantity'    => $qty,
                    'storeId'     => $this->apiHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId),
                    'warrantable' => $this->offers->orderItemHasOffers($orderItem),
                    'product'     => $product
                ];
                break;
            default:
                break;
        }

        return $lineItem;
    }

    /**
     * @param $productSku
     * @return array
     */
    protected function prepareProductPayload($productSku) :array
    {
        if (empty($productSku)) {
            return [];
        }

        $product = $this->getProduct($productSku);

        if (!$product) {
            return [];
        }

        return [
            'id'            => $product->getSku(),
            'listPrice'     => $this->helper->formatPrice($product->getFinalPrice()),
            'name'          => $product->getName(),
            'purchasePrice' => $this->helper->formatPrice($product->getFinalPrice())
        ];
    }

    /**
     * @param OrderItemInterface $orderItem
     * @return array
     */
    protected function getPlan(OrderItemInterface $orderItem): array
    {
        $plan = [];
        $warrantyId = $orderItem->getProductOptionByCode(Type::WARRANTY_ID);
        $warrantyId = is_array($warrantyId) ? array_shift($warrantyId) : $warrantyId;

        $plan = [
            'purchasePrice' => $this->helper->formatPrice($orderItem->getPrice()),
            'id'            => $warrantyId,
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
    protected function getProduct(string $sku): ?ProductInterface
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
    protected function getCustomerData(OrderInterface $order) : array
    {
        $billingAddress = $order->getBillingAddress();
        $billingCountryId = $billingAddress->getCountryId();
        $billingCountryInfo = $this->countryInformationAcquirer->getCountryInfo($billingCountryId);
        $billingStreet = $this->formatStreet($billingAddress->getStreet());

        $customer = [
            'name'      => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
            'email'     => $order->getCustomerEmail(),
            'phone'     => $billingAddress->getTelephone(),
            'billingAddress'    => [
                'address1'      => $billingStreet['address1'] ?? '',
                'address2'      => $billingStreet['address2'] ?? '',
                'city'          => $billingAddress->getCity(),
                'countryCode'   => $billingCountryInfo->getThreeLetterAbbreviation(),
                'postalCode'    => $billingAddress->getPostcode(),
                'province'      => $billingAddress->getRegionCode() ?? ''
            ],
        ];

        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $shippingCountryId = $shippingAddress->getCountryId();
            $shippingCountryInfo = $this->countryInformationAcquirer->getCountryInfo($shippingCountryId);
            $shippingStreet = $this->formatStreet($shippingAddress->getStreet());

            $customer['shippingAddress'] = [
                'address1'      => $shippingStreet['address1'] ?? '',
                'address2'      => $shippingStreet['address2'] ?? '',
                'city'          => $shippingAddress->getCity(),
                'countryCode'   => $shippingCountryInfo->getThreeLetterAbbreviation(),
                'postalCode'    => $shippingAddress->getPostcode(),
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
        $status = '';
        if (!$this->apiHelper->getOrdersApiCreateMode()) {
            $status = 'fulfilled';
        } else {
            $status = 'unfulfilled';
        }

        return $status;
    }
}
