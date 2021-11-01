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

use Extend\Warranty\Model\Product\Type;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Checkout\Controller\Cart;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Extend\Warranty\Helper\Api as Helper;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Add
 */
class Add extends Cart implements HttpPostActionInterface
{
    /**
     * Product Repository Interface
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Search Criteria Builder
     *
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Helper
     *
     * @var Helper
     */
    private $helper;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Add constructor
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CheckoutSession $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param FormKeyValidator $formKeyValidator
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Helper $helper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        FormKeyValidator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Helper $helper,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->helper = $helper;
        $this->logger = $logger;

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
     * Init warranty
     *
     * @return ProductInterface|bool
     */
    protected function initWarranty()
    {
        $this->searchCriteriaBuilder->setPageSize(1);
        $this->searchCriteriaBuilder->addFilter(ProductInterface::TYPE_ID, Type::TYPE_CODE);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResults = $this->productRepository->getList($searchCriteria);
        $results = $searchResults->getItems();

        return reset($results);
    }

    /**
     * Add to cart warranty
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $request = $this->getRequest();
        $warrantyData = $request->getPost('warranty', []);

        if (!$this->_formKeyValidator->validate($request)) {
            $this->messageManager->addErrorMessage(
                __('Sorry! We can\'t add this product protection to your shopping cart right now.')
            );
            $this->logger->error('Invalid form key. Warranty data: ' . $this->helper->getWarrantyDataAsString($warrantyData));

            return $this->_goBack();
        }

        try {
            $warranty = $this->initWarranty();
            if (!$warranty) {
                $this->messageManager->addErrorMessage(
                    __('Sorry! We can\'t add this product protection to your shopping cart right now.')
                );
                $this->logger->error(
                    'Oops! There was an error finding the protection plan product, please ensure the protection plan product is in your catalog and is enabled! '
                    . 'Warranty data: ' . $this->helper->getWarrantyDataAsString($warrantyData)
                );

                return $this->_goBack();
            }

            $errors = $this->helper->validateWarranty($warrantyData);
            if (!empty($errors)) {
                $this->messageManager->addErrorMessage(
                    __('Sorry! We can\'t add this product protection to your shopping cart right now.')
                );
                $errorsAsString = implode(' ', $errors);
                $this->logger->error(
                    'Invalid warranty data. ' . $errorsAsString . ' Warranty data: ' . $this->helper->getWarrantyDataAsString($warrantyData)
                );

                return $this->_goBack();
            }

            $relatedProduct = $warrantyData['product'];
            $qty = 1;

            $quote = $this->_checkoutSession->getQuote();
            foreach ($quote->getAllVisibleItems() as $item) {
                if ($item->getSku() === $relatedProduct) {
                    $qty = $item->getQty();
                    break;
                }
            }

            $warrantyData['qty'] = $qty;

            $this->cart->addProduct($warranty, $warrantyData);
            $this->cart->save();

            $this->messageManager->addSuccessMessage(
                __('You added %1 to your shopping cart.', $warranty->getName())
            );

            return $this->_goBack();
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Sorry! We can\'t add this product protection to your shopping cart right now.')
            );
            $this->logger->critical($e);

            return $this->_goBack();
        }
    }
}
