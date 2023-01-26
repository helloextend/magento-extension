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
use Extend\Warranty\Model\WarrantyRelation;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Checkout\Controller\Cart;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Controller\ResultInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Extend\Warranty\Helper\Tracking as TrackingHelper;
use Extend\Warranty\Model\Offers as OfferModel;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Add
 *
 * Add Cart Controller
 */
class Add extends Cart
{
    /**
     * Product Repository Model
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Search Criteria Builder Model
     *
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Warranty Tracking Helper
     *
     * @var TrackingHelper
     */
    private $trackingHelper;

    /**
     * Offer
     *
     * @var OfferModel
     */
    private $offerModel;

    /**
     * Logger Model
     *
     * @var LoggerInterface
     */
    private $logger;

    private $warrantyRelation;

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
     * @param TrackingHelper $trackingHelper
     * @param OfferModel $offerModel
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context                    $context,
        ScopeConfigInterface       $scopeConfig,
        CheckoutSession            $checkoutSession,
        StoreManagerInterface      $storeManager,
        FormKeyValidator           $formKeyValidator,
        CustomerCart               $cart,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder      $searchCriteriaBuilder,
        TrackingHelper             $trackingHelper,
        OfferModel                 $offerModel,
        LoggerInterface            $logger,
        WarrantyRelation           $warrantyRelation
    )
    {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->trackingHelper = $trackingHelper;
        $this->offerModel = $offerModel;
        $this->logger = $logger;
        $this->warrantyRelation = $warrantyRelation;

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
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $request = $this->getRequest();
        $warrantyData = $request->getPost('warranty', []);

        if (!$this->_formKeyValidator->validate($request)) {
            $this->messageManager->addErrorMessage(
                __('Sorry! We can\'t add this product protection to your shopping cart right now.')
            );
            $this->logger->error(
                'Invalid form key. Warranty data: ' . $this->offerModel->getWarrantyDataAsString($warrantyData)
            );
            $responseData = [
                'status' => false,
                'error' => 'Invalid form key',
            ];

            return $this->jsonResponse($responseData);
        }

        try {
            $warranty = $this->initWarranty();
            if (!$warranty) {
                $this->messageManager->addErrorMessage(
                    __('Sorry! We can\'t add this product protection to your shopping cart right now.')
                );
                $this->logger->error(
                    'Oops! There was an error finding the protection plan product,' .
                    ' please ensure the protection plan product is in your catalog and is enabled! '
                    . 'Warranty data: ' . $this->offerModel->getWarrantyDataAsString($warrantyData)
                );
                $responseData = [
                    'status' => false,
                    'error' => 'Oops! There was an error finding the protection plan product,' .
                        ' please ensure the protection plan product is in your catalog and is enabled!'
                ];

                return $this->jsonResponse($responseData);
            }

            $errors = $this->offerModel->validateWarranty($warrantyData);
            if (!empty($errors)) {
                $this->messageManager->addErrorMessage(
                    __('Sorry! We can\'t add this product protection to your shopping cart right now.')
                );
                $errorsAsString = implode(' ', $errors);
                $this->logger->error(
                    'Invalid warranty data. ' . $errorsAsString . ' Warranty data: ' .
                    $this->offerModel->getWarrantyDataAsString($warrantyData)
                );
                $responseData = [
                    'status' => false,
                    'error' => 'Invalid warranty data',
                ];

                return $this->jsonResponse($responseData);
            }

            $qty = $this->getQtyForWarranty($warrantyData);

            $warrantyData['qty'] = $qty;

            if ($qty) {
                $this->cart->addProduct($warranty, $warrantyData);
                $this->cart->save();
            }

            $this->messageManager->addSuccessMessage(
                __('You added %1 to your shopping cart.', $warranty->getName())
            );

            $responseData = [
                'status' => true,
                'error' => '',
            ];

            if ($this->trackingHelper->isTrackingEnabled()) {
                $trackingData = [
                    'eventName' => 'trackOfferAddedToCart',
                    'productId' => $warrantyData['product'] ?? '',
                    'productQuantity' => $qty,
                    'warrantyQuantity' => $qty,
                    'planId' => $warrantyData['planId'] ?? '',
                    'area' => 'cart_page',
                    'component' => 'modal',
                ];

                $responseData['trackingData'] = $trackingData;
            }

            return $this->jsonResponse($responseData);
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Sorry! We can\'t add this product protection to your shopping cart right now.')
            );
            $this->logger->critical($e);
            $responseData = [
                'status' => false,
                'error' => $e->getMessage(),
            ];

            return $this->jsonResponse($responseData);
        }
    }

    /**
     * JSON response builder
     *
     * @param array $data
     * @return ResultInterface
     */
    private function jsonResponse(array $data = []): ResultInterface
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);

        return $resultJson;
    }

    /**
     * Calculates required qty for a warranty
     *
     * @param $warrantyData
     * @return float
     */
    private function getQtyForWarranty($warrantyData)
    {
        /**
         * We need related item to get Qty
         */
        $relatedQuoteItem = $this->warrantyRelation->getRelatedQuoteItemByWarrantyData($warrantyData);

        $relatedQty = $relatedQuoteItem->getTotalQty();

        $existedWarranties = $this->warrantyRelation->getWarrantiesByQuoteItem($relatedQuoteItem);
        foreach ($existedWarranties as $warrantyItem) {
            $relatedQty -= $warrantyItem->getQty();
        }

        return $relatedQty;

    }
}
