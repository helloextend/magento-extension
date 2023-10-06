<?php
    /**
     * Extend Warranty
     *
     * @author      Extend Magento Team <magento@extend.com>
     * @category    Extend
     * @package     Warranty
     * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
     */

    namespace Extend\Warranty\Controller\Adminhtml\Warranty;

    use Extend\Warranty\Model\Offers as OfferModel;
    use Magento\Backend\App\Action;
    use Magento\Backend\Model\Session\Quote;
    use Magento\Backend\Model\UrlInterface;
    use Magento\Framework\Controller\ResultFactory;
    use Magento\Store\Model\StoreManagerInterface;
    use Magento\Sales\Model\AdminOrder\Create as OrderCreate;
    use Extend\Warranty\Model\Product\Type as WarrantyType;
    use Magento\Catalog\Api\ProductRepositoryInterface;
    use Magento\Framework\Api\SearchCriteriaBuilder;
    use Magento\Framework\Serialize\SerializerInterface;
    use Magento\Sales\Model\OrderRepository;
    use Magento\Quote\Model\QuoteManagement;
    use Magento\Quote\Model\QuoteRepository;
    use Magento\Quote\Model\QuoteFactory;
    use Magento\Customer\Api\Data\CustomerInterfaceFactory;
    use Magento\Customer\Api\CustomerRepositoryInterface;
    use Magento\Framework\DataObject\Factory as DataObjectFactory;
    use Magento\Customer\Api\Data\CustomerInterface;
    use Magento\Framework\Exception\LocalizedException;
    use Magento\Framework\App\ResponseInterface;
    use Magento\Framework\Controller\Result\Json;
    use Magento\Framework\Controller\ResultInterface;
    use Exception;
    use Psr\Log\LoggerInterface;
    use Magento\Payment\Model\Config as PaymentConfig;

    class Leads extends Action
    {
        /**
         * ProductRepository Model
         *
         * @var ProductRepositoryInterface
         */
        protected $productRepository;

        /**
         * OrderCreate Model
         *
         * @var OrderCreate
         */
        protected $orderCreate;

        /**
         * SearchCriteriaBuilder Model
         *
         * @var SearchCriteriaBuilder
         */
        protected $searchCriteriaBuilder;

        /**
         * Serializer Model
         *
         * @var SerializerInterface
         */
        protected $serializer;

        /**
         * Store Manager Model
         *
         * @var StoreManagerInterface
         */
        protected $storeManager;

        /**
         * OrderRepository Model
         *
         * @var OrderRepository
         */
        protected $orderRepository;

        /**
         * QuoteManagement Model
         *
         * @var QuoteManagement
         */
        protected $quoteManagement;

        /**
         * QuoteFactory Model
         *
         * @var QuoteFactory
         */
        protected $quoteFactory;

        /**
         * CustomerFactory Model
         *
         * @var CustomerInterfaceFactory
         */
        protected $customerFactory;

        /**
         * CustomerRepository Model
         *
         * @var CustomerRepositoryInterface
         */
        protected $customerRepository;

        /**
         * QuoteRepository Model
         *
         * @var QuoteRepository
         */
        protected $quoteRepository;

        /**
         * DataObjectFactory Model
         *
         * @var DataObjectFactory
         */
        protected $dataObjectFactory;

        /**
         * @var Quote
         */
        protected $quoteSession;

        /**
         * @var UrlInterface
         */
        protected $url;

        /**
         * @var OfferModel
         */
        protected $offerModel;

        /**
         * @var LoggerInterface
         */
        protected $logger;

        /**
         * @var PaymentConfig
         */
        protected $paymentConfig;

        /**
         * Leads constructor
         *
         * @param Action\Context $context
         * @param StoreManagerInterface $storeManager
         * @param ProductRepositoryInterface $productRepository
         * @param SearchCriteriaBuilder $searchCriteriaBuilder
         * @param SerializerInterface $serializer
         * @param OrderCreate $orderCreate
         * @param OrderRepository $orderRepository
         * @param QuoteManagement $quoteManagement
         * @param QuoteFactory $quoteFactory
         * @param CustomerInterfaceFactory $customerFactory
         * @param CustomerRepositoryInterface $customerRepository
         * @param QuoteRepository $quoteRepository
         * @param DataObjectFactory $dataObjectFactory
         * @param Quote $quoteSession
         * @param UrlInterface $url
         * @param OfferModel $offerModel
         * @param LoggerInterface $logger
         * @param PaymentConfig $paymentConfig
         */
        public function __construct(
            Action\Context              $context,
            StoreManagerInterface       $storeManager,
            ProductRepositoryInterface  $productRepository,
            SearchCriteriaBuilder       $searchCriteriaBuilder,
            SerializerInterface         $serializer,
            OrderCreate                 $orderCreate,
            OrderRepository             $orderRepository,
            QuoteManagement             $quoteManagement,
            QuoteFactory                $quoteFactory,
            CustomerInterfaceFactory    $customerFactory,
            CustomerRepositoryInterface $customerRepository,
            QuoteRepository             $quoteRepository,
            DataObjectFactory           $dataObjectFactory,
            Quote                       $quoteSession,
            UrlInterface                $url,
            OfferModel                  $offerModel,
            LoggerInterface             $logger,
            PaymentConfig               $paymentConfig
        )
        {
            parent::__construct($context);

            $this->productRepository = $productRepository;
            $this->searchCriteriaBuilder = $searchCriteriaBuilder;
            $this->serializer = $serializer;
            $this->storeManager = $storeManager;
            $this->orderCreate = $orderCreate;
            $this->orderRepository = $orderRepository;
            $this->quoteManagement = $quoteManagement;
            $this->quoteFactory = $quoteFactory;
            $this->customerFactory = $customerFactory;
            $this->customerRepository = $customerRepository;
            $this->quoteRepository = $quoteRepository;
            $this->dataObjectFactory = $dataObjectFactory;
            $this->quoteSession = $quoteSession;
            $this->url = $url;
            $this->offerModel = $offerModel;
            $this->logger = $logger;
            $this->paymentConfig = $paymentConfig;
        }

        /**
         * Init warranty
         *
         * @return false|mixed
         */
        protected function initWarranty()
        {
            $this->searchCriteriaBuilder
                ->setPageSize(1)->addFilter('type_id', WarrantyType::TYPE_CODE);

            $searchCriteria = $this->searchCriteriaBuilder->create();
            $searchResults = $this->productRepository->getList($searchCriteria);
            $results = $searchResults->getItems();

            return reset($results);
        }

        /**
         * Get Customer by email
         *
         * @param string $customerEmail
         * @return CustomerInterface
         * @throws LocalizedException
         */
        protected function getCustomer(string $customerEmail)
        {
            $this->searchCriteriaBuilder
                ->setPageSize(1)->addFilter('email', $customerEmail);

            $searchCriteria = $this->searchCriteriaBuilder->create();
            $searchResults = $this->customerRepository->getList($searchCriteria);
            $results = $searchResults->getItems();

            return reset($results);
        }

        /**
         * Add warranty leads product
         *
         * @return ResponseInterface|Json|ResultInterface
         */
        public function execute()
        {
            try {
                $warranty = $this->initWarranty();
                $warrantyData = $this->getRequest()->getPost('warranty');
                $orderId = $this->getRequest()->getPost('order');
                $warrantyData['leadToken'] = $this->getRequest()->getPost('leadToken');
                $warrantyDataRequest = $this->dataObjectFactory->create($warrantyData);

                if (!$warranty) {
                    $errorMessage = __('Oops! There was an error finding the protection plan product,' .
                                       ' please ensure the protection plan product is in your catalog and is enabled!');

                    $data = [
                        "status" => "fail",
                        'error' => $errorMessage
                    ];

                    $this->logger->error(
                        'Oops! There was an error finding the protection plan product,' .
                        ' please ensure the protection plan product is in your catalog and is enabled! '
                        . 'Warranty data: ' . $this->offerModel->getWarrantyDataAsString($warrantyData)
                    );
                    return $this->jsonResponse($data);
                }

                $orderInit = $this->orderRepository->get($orderId);
                $store = $this->storeManager->getStore();
                $quote = $this->quoteFactory->create();
                $customer = $this->getCustomer($orderInit->getCustomerEmail());

                if (!$customer) {
                    $customer = $this->customerFactory->create();
                    $customer->setFirstname($orderInit->getCustomerFirstname())
                        ->setLastname($orderInit->getCustomerLastname())
                        ->setEmail($orderInit->getCustomerEmail());
                    $quote->setCustomerIsGuest(true);
                    $customer = $this->customerRepository->save($customer);
                }

                $billingAddress = [
                    'firstname' => $orderInit->getCustomerFirstname(),
                    'lastname' => $orderInit->getCustomerLastname(),
                    'street' => $orderInit->getBillingAddress()->getStreet(),
                    'city' => $orderInit->getBillingAddress()->getCity(),
                    'country_id' => $orderInit->getBillingAddress()->getCountryId(),
                    'region_id' => $orderInit->getBillingAddress()->getRegionId(),
                    'postcode' => $orderInit->getBillingAddress()->getPostcode(),
                    'telephone' => $orderInit->getBillingAddress()->getTelephone()
                ];

                $quote->setStore($store);
                $quote->assignCustomer($customer);
                $quote->getBillingAddress()->addData($billingAddress);
                $quote->addProduct($warranty, $warrantyDataRequest);

                //retrieve list of active payment methods
                $paymentMethods = $this->paymentConfig->getActiveMethods();
                $paymentArray = array();

                foreach ($paymentMethods as $methodname =>$method) {
                    if ($methodname <>'free'
                        && $methodname <>'paypal_billing_agreement'
                    ){
                        $paymentArray[] = $methodname;
                    }
                }

                sort($paymentArray);
                if (in_array('checkmo', $paymentArray)){
                    //assign the first element of the array to checkmo if available
                    $var = $paymentArray[array_search('checkmo', $paymentArray)];
                    unset($paymentArray[array_search('checkmo', $paymentArray)]);
                    array_unshift($paymentArray, $var);
                }

                $quote->setPaymentMethod($paymentArray[0]);
                $this->quoteRepository->save($quote);
                $quote->getPayment()->importData(['method' => $paymentArray[0]]);
                $quote->collectTotals();
                $this->quoteRepository->save($quote);

                $session = $this->quoteSession;
                $session->setCurrencyId($orderInit->getOrderCurrencyCode());
                $session->setCustomerId($customer->getId() ?: false);
                $session->setStoreId($orderInit->getStoreId());
                $session->setQuoteId($quote->getId());

                $data = [
                    "status" => "success",
                    "redirect" => $this->url->getUrl('sales/order_create/')
                ];
            } catch (Exception $e) {
                $errorMessage = __(
                    'Sorry! We can\'t add this product protection to your shopping cart right now. ' .
                    '<br/> Check extend logs for more details.'
                );

                $data = [
                    "status" => "fail",
                    'error' => $errorMessage
                ];
                $this->logger->critical($e);
            }

            return $this->jsonResponse($data);
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
    }
