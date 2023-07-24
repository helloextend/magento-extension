<?php
/**
 * @deprecated 1.3.0 Orders API should be used in all circumstances instead of the Contracts API.
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Request;

use Extend\Warranty\Helper\Data as DataHelper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Locale\Currency;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Extend\Warranty\Model\Product\Type;
use Exception;

/**
 * Class ContractBuilder
 *
 * Warranty ContractBuilder
 *
 * @deprecated 1.3.0 Orders API should be used in all circumstances instead of the Contracts API
 */
class ContractBuilder
{
    /**
     * Platform code
     */
    public const PLATFORM_CODE = 'magento';

    /**
     * Product Repository Model
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Store Manager Model
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Country Information Acquirer Model
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
     * ContractBuilder constructor
     *
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param CountryInformationAcquirerInterface $countryInformationAcquirer
     * @param DataHelper $helper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        CountryInformationAcquirerInterface $countryInformationAcquirer,
        DataHelper $helper
    ) {
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->countryInformationAcquirer = $countryInformationAcquirer;
        $this->helper = $helper;
    }

    /**
     * Prepare payload
     *
     * @param OrderInterface $order
     * @param OrderItemInterface $orderItem
     * @param string $type
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function preparePayload(OrderInterface $order, OrderItemInterface $orderItem, string $type): array
    {
        $productSku = $orderItem->getProductOptionByCode(Type::ASSOCIATED_PRODUCT);
        $productSku = is_array($productSku) ? array_shift($productSku) : $productSku;

        $warrantyId = $orderItem->getProductOptionByCode(Type::WARRANTY_ID);
        $warrantyId = is_array($warrantyId) ? array_shift($warrantyId) : $warrantyId;

        if (empty($productSku) || empty($warrantyId)) {
            return [];
        }

        $product = $this->getProduct($productSku);

        if ($type == \Extend\Warranty\Model\WarrantyContract::LEAD_CONTRACT) {
            $leadToken = $orderItem->getLeadToken() ?? '';

            if (!empty($leadToken)) {
                try {
                    $leadToken = implode(", ", $this->helper->unserialize($leadToken));
                } catch (Exception $exception) {
                    $leadToken = '';
                }
            }
        }

        if (!$product) {
            return [];
        }

        $currencyCode = $order->getOrderCurrencyCode();

        if (!$currencyCode) {
            $store = $this->storeManager->getStore();
            $currencyCode = $store->getBaseCurrencyCode() ?? Currency::DEFAULT_CURRENCY;
        }

        $transactionTotal = [
            'currencyCode'  => $currencyCode,
            'amount'        => $this->helper->formatPrice($order->getBaseGrandTotal()),
        ];

        $billingAddress = $order->getBillingAddress();
        $billingCountryId = $billingAddress->getCountryId();
        $billingCountryInfo = $this->countryInformationAcquirer->getCountryInfo($billingCountryId);
        $billingStreet = $this->formatStreet($billingAddress->getStreet());

        $customer = [
            'name'      => $this->helper->getCustomerFullName($order),
            'email'     => $order->getCustomerEmail(),
            'phone'     => $billingAddress->getTelephone(),
            'billingAddress'    => [
                'address1'      => $billingStreet['address1'] ?? '',
                'address2'      => $billingStreet['address2'] ?? '',
                'city'          => $billingAddress->getCity(),
                'countryCode'   => $billingCountryInfo->getThreeLetterAbbreviation(),
                'postalCode'    => $billingAddress->getPostcode(),
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

        $product = [
            'referenceId'   => $product->getSku(),
            'purchasePrice' => [
                'currencyCode'  => $currencyCode,
                'amount'        => $this->helper->formatPrice($product->getFinalPrice()),
            ],
        ];

        $source = [
            'platform'  => self::PLATFORM_CODE,
        ];

        $plan = [
            'purchasePrice' => [
                'currencyCode'  => $currencyCode,
                'amount'        => $this->helper->formatPrice($orderItem->getPrice()),
            ],
            'planId'        => $warrantyId,
        ];

        $createdAt = $order->getCreatedAt();

        if ($type == \Extend\Warranty\Model\WarrantyContract::CONTRACT) {
            $payload = [
                'transactionId' => $order->getIncrementId(),
                'transactionTotal' => $transactionTotal,
                'customer' => $customer,
                'product' => $product,
                'currency' => $currencyCode,
                'source' => $source,
                'transactionDate' => $createdAt ? strtotime($createdAt) : 0,
                'plan' => $plan,
            ];
        }

        if ($type == \Extend\Warranty\Model\WarrantyContract::LEAD_CONTRACT) {
            $payload = [
                'transactionId' => $order->getIncrementId(),
                'transactionTotal' => $transactionTotal,
                'customer' => $customer,
                'leadToken' => $leadToken,
                'currency' => $currencyCode,
                'source' => $source,
                'transactionDate' => $createdAt ? strtotime($createdAt) : 0,
                'plan' => $plan,
            ];
        }

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
     * Get product
     *
     * @param string $sku
     * @return ProductInterface|null
     */
    protected function getProduct(string $sku)
    {
        try {
            $product = $this->productRepository->get($sku);
        } catch (LocalizedException $exception) {
            $product = null;
        }

        return $product;
    }
}
