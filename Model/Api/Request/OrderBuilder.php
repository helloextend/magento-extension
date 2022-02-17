<?php

namespace Extend\Warranty\Model\Api\Request;

use Extend\Warranty\Helper\Data as DataHelper;
use Extend\Warranty\Helper\Api\Data as ApiDataHelper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Extend\Warranty\Model\Product\Type;

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
     * ContractBuilder constructor
     *
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param CountryInformationAcquirerInterface $countryInformationAcquirer
     * @param DataHelper $helper
     * @param ApiDataHelper $apiHelper
     */
    public function __construct (
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        CountryInformationAcquirerInterface $countryInformationAcquirer,
        DataHelper $helper,
        ApiDataHelper $apiHelper
    ) {
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->countryInformationAcquirer = $countryInformationAcquirer;
        $this->helper = $helper;
        $this->apiHelper = $apiHelper;
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
        $store = $this->storeManager->getStore();
        $currencyCode = $store->getBaseCurrencyCode();
        $transactionTotal = $this->helper->formatPrice($order->getBaseGrandTotal());
        $lineItem = [];

        if ( $type == \Extend\Warranty\Model\Orders::CONTRACT) {
            $productSku = $orderItem->getProductOptionByCode(Type::ASSOCIATED_PRODUCT);
            $productSku = is_array($productSku) ? array_shift($productSku) : $productSku;

            $plan = $this->getPlan($orderItem);

            $product = $this->prepareProductPayload($productSku);

            $lineItem = [
                'status'      => $this->getStatus(),
                'quantity'    => $qty,
                'storeId'     => $this->apiHelper->getStoreId(),
                'warrantable' => true,
                'product'     => $product,
                'plan'        => $plan
            ];

        } elseif ($type == \Extend\Warranty\Model\Orders::LEAD) {
            $productSku = $orderItem->getSku();
            $product = $this->prepareProductPayload($productSku);

            $lineItem = [
                'status'      => $this->getStatus(),
                'quantity'    => $qty,
                'storeId'     => $this->apiHelper->getStoreId(),
                'warrantable' => true,
                'product'     => $product
            ];
        } elseif ($type == \Extend\Warranty\Model\Orders::LEAD_CONTRACT) {
            $productSku = $orderItem->getSku();
            $product = $this->prepareProductPayload($productSku);

            $plan = $this->getPlan($orderItem);

            $lineItem = [
                'status'      => $this->getStatus(),
                'quantity'    => $qty,
                'storeId'     => $this->apiHelper->getStoreId(),
                'warrantable' => true,
                'plan'        => $plan,
                'leadToken'     => implode(", ", json_decode($orderItem->getLeadToken(), true))
            ];
        }

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

        $result = [
            'id'            => $product->getSku(),
            'listPrice'     => $this->helper->formatPrice($product->getFinalPrice()),
            'name'          => $product->getName(),
            'purchasePrice' => $this->helper->formatPrice($product->getFinalPrice())
        ];

        return $result;
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
        return 'fulfilled';
    }
}
