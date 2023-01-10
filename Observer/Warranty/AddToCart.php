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

use Extend\Warranty\Helper\Data as WarrantyHelper;
use Extend\Warranty\Model\Product\Type as WarrantyProductType;

/**
 * Class AddToCart
 *
 * AddToCart Observer
 */
class AddToCart implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Magento Cart Helper
     *
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $_cartHelper;

    /**
     * Product Repository Model
     *
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * Search Criteria Builder Model
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * Message Manager Model
     *
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * Warranty Tracking Helper
     *
     * @var \Extend\Warranty\Helper\Tracking
     */
    protected $_trackingHelper;

    /**
     * Logger Model
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Offer
     *
     * @var \Extend\Warranty\Model\Offers
     */
    protected $offerModel;

    /**
     * CartItem Api Data Factory
     *
     * @var \Magento\Quote\Api\Data\CartItemInterfaceFactory
     */
    protected $cartItemFactory;

    /**
     * Warranty Api Data Helper
     *
     * @var \Extend\Warranty\Helper\Api\Data
     */
    protected $dataHelper;

    /**
     * Normalizer Model
     *
     * @var \Extend\Warranty\Model\Normalizer
     */
    protected $normalizer;

    /**
     * @var bool
     */
    protected $saveCartFlag = false;

    /**
     * AddToCart constructor
     *
     * @param \Magento\Checkout\Helper\Cart $cartHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Extend\Warranty\Helper\Tracking $trackingHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Extend\Warranty\Model\Offers $offerModel
     * @param \Magento\Quote\Api\Data\CartItemInterfaceFactory $cartItemFactory
     * @param \Extend\Warranty\Helper\Api\Data $dataHelper
     * @param \Extend\Warranty\Model\Normalizer $normalizer
     */
    public function __construct(
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Extend\Warranty\Helper\Tracking $trackingHelper,
        \Psr\Log\LoggerInterface $logger,
        \Extend\Warranty\Model\Offers $offerModel,
        \Magento\Quote\Api\Data\CartItemInterfaceFactory $cartItemFactory,
        \Extend\Warranty\Helper\Api\Data $dataHelper,
        \Extend\Warranty\Model\Normalizer $normalizer
    ) {
        $this->_cartHelper = $cartHelper;
        $this->_productRepository = $productRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_messageManager = $messageManager;
        $this->_trackingHelper = $trackingHelper;
        $this->_logger = $logger;
        $this->offerModel = $offerModel;
        $this->cartItemFactory = $cartItemFactory;
        $this->dataHelper = $dataHelper;
        $this->normalizer = $normalizer;
    }

    /**
     * Add to cart warranty
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\App\RequestInterface $request */
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $observer->getData('request');
        /** @var \Magento\Checkout\Model\Cart $cart */
        $cart = $this->_cartHelper->getCart();
        $product = $observer->getProduct();

        if ($product->getTypeId() === 'grouped') {
            $items = $request->getPost('super_group');
            foreach ($items as $id => $qty) {
                $warrantyData = $request->getPost('warranty_' . $id, []);
                $this->addWarranty($cart, $warrantyData, $qty, $product);
            }
        } else {
            $qty = $request->getPost('qty', 1);
            $warrantyData = $request->getPost('warranty', []);

            $this->addWarranty($cart, $warrantyData, $qty, $product);
        }

        if($this->saveCartFlag){
            $cart->save();
        }

        if ($this->dataHelper->isBalancedCart()) {
            try {
                /** @var \Magento\Quote\Model\Quote $cart */
                $quote = $this->_cartHelper->getQuote();
                $this->normalizer->normalize($quote);
            } catch (\Magento\Framework\Exception\LocalizedException $exception) {
                $this->_logger->error($exception->getMessage());
            }
        }
    }

    /**
     * Add warranty
     *
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @param array $warrantyData
     * @param int $qty
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     * @throws \Exception
     */
    private function addWarranty($cart, array $warrantyData, int $qty, $product)
    {
        if (empty($warrantyData) || $qty < 1) {
            return;
        }

        $errors = $this->offerModel->validateWarranty($warrantyData);
        if (!empty($errors)) {
            $this->_messageManager->addErrorMessage(
                __('Oops! There was an error adding the protection plan product.')
            );
            $errorsAsString = implode(' ', $errors);
            $this->_logger->error(
                'Invalid warranty data. ' . $errorsAsString .
                ' Warranty data: ' . $this->offerModel->getWarrantyDataAsString($warrantyData)
            );

            return;
        }

        $this->_searchCriteriaBuilder
            ->setPageSize(1)->addFilter('type_id', WarrantyProductType::TYPE_CODE);
        /** @var \Magento\Framework\Api\SearchCriteria $searchCriteria */
        $searchCriteria = $this->_searchCriteriaBuilder->create();
        $searchResults = $this->_productRepository->getList($searchCriteria);
        /** @var \Magento\Catalog\Model\Product[] $results */
        $results = $searchResults->getItems();
        /** @var \Magento\Catalog\Model\Product $warranty */
        $warranty = reset($results);
        if (!$warranty) {
            $this->_messageManager->addErrorMessage(
                'Oops! There was an error adding the protection plan product.'
            );
            $this->_logger->error(
                'Oops! There was an error finding the protection plan product,' .
                ' please ensure the protection plan product is in your catalog and is enabled! ' .
                'Warranty data: ' . $this->offerModel->getWarrantyDataAsString($warrantyData)
            );

            return;
        }
        $relatedItem = $cart->getQuote()->getItemByProduct($product);
        $warrantyData['qty'] = $qty;

        try {
            $cart->addProduct($warranty, $warrantyData);
            $cart->getQuote()->removeAllAddresses();
            /** @noinspection PhpUndefinedMethodInspection */
            $cart->getQuote()->setTotalsCollectedFlag(false);
            $this->saveCartFlag = true;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_logger->critical($e);
            $this->_messageManager->addErrorMessage(
                'Oops! There was an error adding the protection plan product.'
            );

            return;
        }
        if ($this->_trackingHelper->isTrackingEnabled()) {
            if (!isset($warrantyData['component']) || $warrantyData['component'] !== 'modal') {
                $trackingData = [
                    'eventName' => 'trackOfferAddedToCart',
                    'productId' => $warrantyData['product'] ?? '',
                    'productQuantity' => $qty,
                    'warrantyQuantity' => $qty,
                    'planId' => $warrantyData['planId'] ?? '',
                    'area' => 'product_page',
                    'component' => $warrantyData['component'] ?? 'buttons',
                ];
            } else {
                $trackingData = [
                    'eventName' => 'trackOfferUpdated',
                    'productId' => $warrantyData['product'] ?? '',
                    'productQuantity' => $qty,
                    'warrantyQuantity' => $qty,
                    'planId' => $warrantyData['planId'] ?? '',
                    'area' => 'product_page',
                    'component' => $warrantyData['component'] ?? 'buttons',
                ];
            }
            $this->_trackingHelper->setTrackingData($trackingData);
        }
    }
}
