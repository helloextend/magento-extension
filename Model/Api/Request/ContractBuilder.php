<?php


namespace Extend\Warranty\Model\Api\Request;

use Extend\Warranty\Helper\Data;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Extend\Warranty\Model\Product\Type;

class ContractBuilder
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CountryInformationAcquirerInterface
     */
    protected $countryInformationAcquirer;

    /**
     * @var Data
     */
    protected $helper;


    public function __construct
    (
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        CountryInformationAcquirerInterface $countryInformationAcquirer,
        Data $helper
    )
    {

        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->countryInformationAcquirer = $countryInformationAcquirer;
        $this->helper = $helper;
    }

    /**
     * @param Order $order
     * @param $warranties
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareInfo($order, $warranties)
    {
        $contracts = [];

        /** @var \Magento\Sales\Model\Order\Item $warranty */
        foreach ($warranties as $key => $warranty) {
            $productSku = $warranty->getProductOptionByCode(Type::ASSOCIATED_PRODUCT);
            $warrantyId = $warranty->getProductOptionByCode(Type::WARRANTY_ID);

            if (empty($productSku) || empty($warrantyId)) {
                continue;
            }

            try {
                $product = $this->productRepository->get($productSku);
            } catch (NoSuchEntityException $exception) {
                continue;
            }

            $billing = $order->getBillingAddress();

            $shipping = $order->getShippingAddress();


            $contracts[$key] = [
                'transactionId' => $order->getIncrementId(),
                'transactionTotal' => $this->helper->formatPrice($order->getGrandTotal()),
                'customer' => [
                    'phone' => $billing->getTelephone(),
                    'email' => $order->getCustomerEmail(),
                    'name' => $order->getCustomerName(),
                    'billing' => [
                        "postalCode" => $billing->getPostcode(),
                        "city" => $billing->getCity(),
                        "country" => $this->countryInformationAcquirer
                            ->getCountryInfo(
                                $billing->getCountryId()
                            )->getThreeLetterAbbreviation(),
                        "region" => $billing->getRegion()
                    ],
                    'shipping' => [
                        "postalCode" => $shipping->getPostcode(),
                        "city" => $shipping->getCity(),
                        "country" => $this->countryInformationAcquirer
                            ->getCountryInfo(
                                $shipping->getCountryId()
                            )->getThreeLetterAbbreviation(),
                        "region" => $shipping->getRegion()
                    ]
                ],
                'product' => [
                    'referenceId' => $product->getSku(),
                    'purchasePrice' => $this->helper->formatPrice($product->getFinalPrice()),
                    'title' => $product->getName(),
                    'qty' => intval($warranty->getQtyOrdered())
                ],
                'currency' => $this->storeManager->getStore()->getCurrentCurrencyCode(),
                'transactionDate' => $order->getCreatedAt() ? strtotime($order->getCreatedAt()) : strtotime('now'),
                'plan' => [
                    'purchasePrice' => $this->helper->formatPrice($warranty->getPrice()),
                    'planId' => $warrantyId
                ]
            ];

            $billingStreet = $billing->getStreet();
            $billingFormat = $this->formatStreet($billingStreet);

            $contracts[$key]['customer']['billing'] = array_merge(
                $contracts[$key]['customer']['billing'],
                $billingFormat
            );

            $shippingStreet = $shipping->getStreet();
            $shippingFormat = $this->formatStreet($shippingStreet);

            $contracts[$key]['customer']['shipping'] = array_merge(
                $contracts[$key]['customer']['shipping'],
                $shippingFormat
            );

            if (!$order->getCustomerIsGuest()) {
                $contracts[$key]['customer']['customerId'] = $order->getCustomerId();
            }

        }
        return $contracts;
    }

    private function formatStreet($street): array
    {
        $address = [];

        $address['address1'] = array_shift($street);
        if (!empty($street)) {
            $address['address2'] = implode(",",$street);
        }

        return $address;
    }
}