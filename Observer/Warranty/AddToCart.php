<?php

namespace Extend\Warranty\Observer\Warranty;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Extend\Warranty\Model\Product\Type as WarrantyType;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Helper\Cart;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
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

            $warrantyData['qty'] = $qty;

            try {
                $cart->addProduct($warranty->getId(), $warrantyData);

                $cart->getQuote()->removeAllAddresses();
                $cart->getQuote()->setTotalsCollectedFlag(false);
                $cart->save();

                $quote = $cart->getQuote();
                $this->normalize($quote);
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage('Oops! There was an error adding the protection plan product.');
            }

        }
    }

    /**
     * Normilize quote
     *
     * @param CartInterface $quote
     */
    private function normalize(CartInterface $quote): void
    {
        $warranties = [];
        $products = [];

        foreach ($quote->getAllItems() as $item) {
            if ($item->getProductType() === WarrantyType::TYPE_CODE) {
                $warranties[$item->getItemId()] = $item;
            } else {
                $products[] = $item;
            }
        }

        foreach ($products as $item) {
            $sku = $item->getSku();

            foreach ($warranties as $warrantyItem) {
                $associatedProductOption = $warrantyItem->getOptionByCode('associated_product');
                if (
                    $associatedProductOption
                    && $associatedProductOption->getValue() === $sku
                    && ($item->getProductType() === Configurable::TYPE_CODE || is_null($item->getOptionByCode('parent_product_id')))
                ) {
                    if ((int)$warrantyItem->getQty() !== (int)$item->getQty()) {
                        if ($item->getQty() > 0) {
                            $warrantyItem->setQty($item->getQty());
                            $warrantyItem->calcRowTotal();
                            $warrantyItem->save();
                        }
                    }
                }
            }
        }

        $quote->setTriggerRecollect(1);
        $quote->collectTotals();
        $quote->save();
    }
}