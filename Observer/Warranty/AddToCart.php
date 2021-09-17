<?php

namespace Extend\Warranty\Observer\Warranty;

use Extend\Warranty\Model\Product\Type as WarrantyType;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Helper\Cart;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

class AddToCart implements ObserverInterface
{
    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var LoggerInterface
     */
    protected $addWarrantyLogger;

    public function __construct
    (
        Cart $cart,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ManagerInterface $messageManager,
        LoggerInterface $addWarrantyLogger
    )
    {
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->messageManager = $messageManager;
        $this->addWarrantyLogger = $addWarrantyLogger;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $request = $observer->getEvent()->getRequest();
        $cart = $this->cart->getCart();
        $qty = $request->getPost('qty');
        $warrantyData = $request->getPost('warranty');

        if (!empty($warrantyData)) {

            $this->searchCriteriaBuilder
                ->setPageSize(1)->addFilter('type_id', WarrantyType::TYPE_CODE);

            $searchCriteria = $this->searchCriteriaBuilder->create();

            $searchResults = $this->productRepository->getList($searchCriteria);

            $results = $searchResults->getItems();
            $warranty = reset($results);

            if (!$warranty) {
                $this->messageManager->addErrorMessage('Oops! There was an error adding the protection plan product.');
                $this->addWarrantyLogger->error('Oops! There was an error finding the protection pan product, please ensure the Extend protection plan product is in your catalog and is enabled!');
                return;
            }

            $warrantyData['qty'] = !empty($qty) ? $qty : 1;

            try {
                $cart->addProduct($warranty->getId(), $warrantyData);

                $cart->getQuote()->removeAllAddresses();
                $cart->getQuote()->setTotalsCollectedFlag(false);
                $cart->save();

            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage('Oops! There was an error adding the protection plan product.');
            }

        }
    }
}