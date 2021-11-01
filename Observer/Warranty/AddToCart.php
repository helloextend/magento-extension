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

/**
 * Class AddToCart
 * @package Extend\Warranty\Observer\Warranty
 */
class AddToCart implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Cart Helper
     *
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $_cartHelper;

    /**
     * Product Repository Interface
     *
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * Message Manager Interface
     *
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * Logger Interface
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Helper
     *
     * @var \Extend\Warranty\Helper\Api
     */
    protected $helper;

    /**
     * AddToCart constructor
     *
     * @param \Magento\Checkout\Helper\Cart $cartHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Extend\Warranty\Helper\Data $helper
     */
    public function __construct(
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger,
        \Extend\Warranty\Helper\Api $helper
    ) {
        $this->_cartHelper = $cartHelper;
        $this->_productRepository = $productRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_messageManager = $messageManager;
        $this->_logger = $logger;
        $this->helper = $helper;
    }

    /**
     * Add to cart warranty
     *
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

        $errors = $this->helper->validateWarranty($warrantyData);
        if (!empty($errors)) {
            $this->_messageManager->addErrorMessage(
                __('Oops! There was an error adding the protection plan product.')
            );
            $errorsAsString = implode(' ', $errors);
            $this->_logger->error(
                'Invalid warranty data. ' . $errorsAsString . ' Warranty data: ' . $this->helper->getWarrantyDataAsString($warrantyData)
            );

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
            $this->_logger->error(
                'Oops! There was an error finding the protection plan product, please ensure the protection plan product is in your catalog and is enabled! '
                . 'Warranty data: ' . $this->helper->getWarrantyDataAsString($warrantyData)
            );

            return;
        }
        $warrantyData['qty'] = $qty;
        try {
            $cart->addProduct($warranty->getId(), $warrantyData);
            $cart->getQuote()->removeAllAddresses();
            /** @noinspection PhpUndefinedMethodInspection */
            $cart->getQuote()->setTotalsCollectedFlag(false);
            $cart->save();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_logger->critical($e);
            $this->_messageManager->addErrorMessage('Oops! There was an error adding the protection plan product.');

            return;
        }
    }
}
