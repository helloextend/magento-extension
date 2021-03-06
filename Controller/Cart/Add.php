<?php

namespace Extend\Warranty\Controller\Cart;

use Extend\Warranty\Model\Product\Type as WarrantyType;
use Magento\Checkout\Controller\Cart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class Add extends Cart
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var LoggerInterface
     */
    protected $addWarrantyLogger;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SerializerInterface $serializer,
        LoggerInterface $addWarrantyLogger
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->serializer = $serializer;
        $this->addWarrantyLogger = $addWarrantyLogger;
    }

    protected function initWarranty()
    {
        $this->searchCriteriaBuilder
            ->setPageSize(1)->addFilter('type_id', WarrantyType::TYPE_CODE);

        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResults = $this->productRepository->getList($searchCriteria);

        $results = $searchResults->getItems();
        return reset($results);
    }

    public function execute()
    {
        $warrantyData = $this->getRequest()->getPost('warranty');

        try {
            $warranty = $this->initWarranty();
            if (!$warranty) {
                $this->messageManager->addErrorMessage('Sorry! We can\'t add this product protection to your shopping cart right now.');
                $this->addWarrantyLogger->error('Oops! There was an error finding the protection plan product, please ensure the protection plan product is in your catalog and is enabled!');

                return $this->goBack();
            }

            //Check Qty
            $_relatedProduct = $warrantyData['product'];
            $_qty = 1;
            $_quote = $this->_checkoutSession->getQuote();
            foreach($_quote->getAllVisibleItems() as $_item) {
                if ($_item->getSku() == $_relatedProduct) $_qty = $_item->getQty();
            }
            $warrantyData['qty'] = $_qty;

            $this->cart->addProduct($warranty, $warrantyData);
            $this->cart->save();

            $message = __(
                'You added %1 to your shopping cart.',
                $warranty->getName()
            );
            $this->messageManager->addSuccessMessage($message);
            return $this->goBack(null, $warranty);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Sorry! We can\'t add this product protection to your shopping cart right now.')
            );
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            return $this->goBack();
        }
    }

    protected function goBack($backUrl = null, $product = null)
    {
        if (!$this->getRequest()->isAjax()) {
            return parent::_goBack($backUrl);
        }

        $result = [];

        if ($backUrl || $backUrl = $this->getBackUrl()) {
            $result['backUrl'] = $backUrl;
        } else {
            if ($product && !$product->getIsSalable()) {
                $result['product'] = [
                    'statusText' => __('Out of stock')
                ];
            }
        }

        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
        );
    }
}
