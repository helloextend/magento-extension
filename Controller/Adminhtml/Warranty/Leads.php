<?php

namespace Extend\Warranty\Controller\Adminhtml\Warranty;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

use Magento\Sales\Model\AdminOrder\Create as OrderCreate;

use Extend\Warranty\Model\Product\Type as WarrantyType;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;

use Magento\Sales\Model\OrderRepository;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteRepository;

use Magento\Quote\Model\QuoteFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\DataObject\Factory as DataObjectFactory;

class Leads extends Action
{
    const ADMIN_RESOURCE = 'Extend_Warranty::warranty_admin_add';

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var OrderCreate
     */
    protected $orderCreate;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    protected $storeManager;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var CustomerInterfaceFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @param Action\Context $context
     * @param ScopeConfigInterface $scopeConfig
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
     */
    public function __construct(
        Action\Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SerializerInterface $serializer,
        OrderCreate $orderCreate,
        OrderRepository $orderRepository,
        QuoteManagement $quoteManagement,
        QuoteFactory $quoteFactory,
        CustomerInterfaceFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        QuoteRepository $quoteRepository,
        DataObjectFactory $dataObjectFactory
    ) {
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
    }

    /**
     * @return false|mixed
     */
    protected function initWarranty()
    {
        $this->searchCriteriaBuilder
            ->setPageSize(1)->addFilter('type_id', WarrantyType::TYPE_CODE);

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults  = $this->productRepository->getList($searchCriteria);
        $results        = $searchResults->getItems();

        return reset($results);
    }

    /**
     * @return false|mixed
     */
    protected function getCustomer($customerEmail)
    {
        $this->searchCriteriaBuilder
            ->setPageSize(1)->addFilter('email', $customerEmail);

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults  = $this->customerRepository->getList($searchCriteria);
        $results        = $searchResults->getItems();

        return reset($results);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
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
                $data = ["status"=>"fail"];
            }

            $orderInit = $this->orderRepository->get($orderId);

            $store= $this->storeManager->getStore();
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
                'firstname'    => $orderInit->getCustomerFirstname(),
                'lastname'     => $orderInit->getCustomerLastname(),
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
            $quote->setPaymentMethod('checkmo');
            $this->quoteRepository->save($quote);
            $quote->getPayment()->importData(['method' => 'checkmo']);
            $quote->collectTotals();
            $this->quoteRepository->save($quote);

            $order = $this->quoteManagement->submit($quote);

            $data = ["status"=>"success", "redirect" => $this->_url->getUrl('sales/order/view/', ['order_id' => $order->getId()]) ];

        } catch (\Exception $e) {
            $data = ["status"=>"fail"];
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setHttpResponseCode(200)->setData($data);
    }
}
