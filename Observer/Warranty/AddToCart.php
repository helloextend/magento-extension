<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */
namespace Extend\Warranty\Observer\Warranty;

use Magento\Framework\DataObject;

    /**
 * Class AddToCart
 * @package Extend\Warranty\Observer\Warranty
 */
class AddToCart implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $_cartHelper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var \Extend\Warranty\Helper\Tracking
     */
    protected $_trackingHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * AddToCart constructor.
     * @param \Magento\Checkout\Helper\Cart $cartHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Extend\Warranty\Helper\Tracking $trackingHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Extend\Warranty\Helper\Tracking $trackingHelper,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_cartHelper = $cartHelper;
        $this->_productRepository = $productRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_messageManager = $messageManager;
        $this->_trackingHelper = $trackingHelper;
        $this->_logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\App\RequestInterface $request */
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $observer->getData('request');
        /** @var \Magento\Checkout\Model\Cart $cart */
        $cart = $this->_cartHelper->getCart();
        $qty = $request->getPost('qty', 1);
        $warrantyData = $request->getPost('warranty', []);
        if (empty($warrantyData)) {
            return;
        }
        $this->_searchCriteriaBuilder
            ->setPageSize(1)->addFilter('type_id', \Extend\Warranty\Model\Product\Type::TYPE_CODE);
        /** @var \Magento\Framework\Api\SearchCriteria $searchCriteria */
        $searchCriteria = $this->_searchCriteriaBuilder->create();
        $searchResults = $this->_productRepository->getList($searchCriteria);
        /** @var \Magento\Catalog\Model\Product[] $results */
        $results = $searchResults->getItems();
        /** @var \Magento\Catalog\Model\Product $warranty */
        $warranty = reset($results);
        if (!$warranty) {
            $this->_messageManager->addErrorMessage('Oops! There was an error adding the protection plan product.');
            $this->_logger->error('Oops! There was an error finding the protection pan product, please ensure the Extend protection plan product is in your catalog and is enabled!');
            return;
        }
        $warrantyData['qty'] = $qty;
        try {

            $warrantyRequest = new DataObject();
            $warrantyRequest->setData($warrantyData);

            $quote = $cart->getQuote();
            $quote->addProduct($warranty, $warrantyRequest);
            //$cart->getQuote()->removeAllAddresses();
            /** @noinspection PhpUndefinedMethodInspection */
            $cart->getQuote()->setTotalsCollectedFlag(false);
            $cart->save();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_logger->critical($e);
            $this->_messageManager->addErrorMessage('Oops! There was an error adding the protection plan product.');
            return;
        }
        if (!$this->_trackingHelper->isTrackingEnabled()) {
            return;
        }
        $trackingData = [
            'eventName'        => 'trackOfferAddedToCart',
            'productId'        => $warrantyData['product'] ?? '',
            'productQuantity'  => $qty,
            'warrantyQuantity' => $qty,
            'planId'           => $warrantyData['planId'] ?? '',
            'area'             => 'product_page',
            'component'        => $warrantyData['component'] ?? 'buttons',
        ];
        $this->_trackingHelper->setTrackingData($trackingData);
    }
}
