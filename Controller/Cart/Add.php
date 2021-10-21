<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */
namespace Extend\Warranty\Controller\Cart;

/**
 * Class Add
 * @package Extend\Warranty\Controller\Cart
 */
class Add extends \Magento\Checkout\Controller\Cart
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $_serializer;

    /**
     * @var \Extend\Warranty\Helper\Tracking
     */
    protected $_trackingHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Add constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Extend\Warranty\Helper\Tracking $trackingHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @noinspection PhpDeprecationInspection
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Extend\Warranty\Helper\Tracking $trackingHelper,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_productRepository = $productRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_serializer = $serializer;
        $this->_trackingHelper = $trackingHelper;
        $this->_logger = $logger;

        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
    }

    /**
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    protected function initWarranty()
    {
        $this->_searchCriteriaBuilder->setPageSize(1)->addFilter('type_id', \Extend\Warranty\Model\Product\Type::TYPE_CODE);
        /** @var \Magento\Framework\Api\SearchCriteria $searchCriteria */
        $searchCriteria = $this->_searchCriteriaBuilder->create();
        /** @var \Magento\Catalog\Api\Data\ProductSearchResultsInterface $searchResults */
        $searchResults = $this->_productRepository->getList($searchCriteria);
        /** @var \Magento\Catalog\Api\Data\ProductInterface[] $results */
        $results = $searchResults->getItems();

        return reset($results);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    public function execute()
    {
        $warrantyData = $this->getRequest()->getPost('warranty', []);
        try {
            $warranty = $this->initWarranty();
            if (!$warranty) {
                $this->messageManager->addErrorMessage(__('Sorry! We can\'t add this product protection to your shopping cart right now.'));
                $this->_logger->error('Oops! There was an error finding the protection plan product, please ensure the protection plan product is in your catalog and is enabled!');
                return $this->goBack();
            }
            //Check Qty
            $relatedProduct = $warrantyData['product'];
            $qty = 1;
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->_checkoutSession->getQuote();
            foreach($quote->getAllVisibleItems() as $item) {
                /** @var \Magento\Quote\Model\Quote\Item $item */
                if ($item->getSku() == $relatedProduct) {
                    $qty = $item->getQty();
                    break(1);
                }
            }
            $warrantyData['qty'] = $qty;
            /** @var \Magento\Catalog\Model\Product $warranty */
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
            $this->_logger->critical($e);
            return $this->goBack();
        }
    }

    /**
     * @param null $backUrl
     * @param \Magento\Catalog\Model\Product|null $product
     * @return \Magento\Framework\Controller\Result\Redirect|void
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
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
        $this->getResponse()->representJson($this->_serializer->serialize($result));
    }
}
