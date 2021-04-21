<?php

namespace Extend\Warranty\Model\Api\Request;

use Extend\Warranty\Helper\Data;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order;
use Extend\Warranty\Model\Product\Type;

class LeadBuilder
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
     * @var Data
     */
    protected $helper;


    public function __construct
    (
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        Data $helper
    )
    {
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
    }

    /**
     * @param $order
     * @param $product
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareInfo($order, $product)
    {
        $lead = [
            'customer' => [
                "email" => $order->getCustomerEmail()
                ],
            'quantity' => $product->getQtyOrdered(),
            'product' => [
                'purchasePrice' => [
                    'currencyCode' => $order->getBaseCurrencyCode(),
                    'amount' => $product->getPrice()
                ],
                'referenceId' => $product->getSku(),
                'transactionDate' => time() * 1000,
                'transactionId' => $order->getIncrementId()
            ]
        ];
        return $lead;
    }
}
