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

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Model\OrderRepository;

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

    protected $customerRepository;

    protected $orderRepository;

    public function __construct(
        Action\Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SerializerInterface $serializer,
        OrderCreate $orderCreate,
        CustomerRepositoryInterface $customerRepository,
        OrderRepository $orderRepository
    ) {
        parent::__construct($context);

        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->serializer = $serializer;
        $this->orderCreate = $orderCreate;
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
    }

    protected function initWarranty()
    {
        $this->searchCriteriaBuilder
            ->setPageSize(1)->addFilter('type_id', WarrantyType::TYPE_CODE);

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults  = $this->productRepository->getList($searchCriteria);
        $results        = $searchResults->getItems();

        return reset($results);
    }

    public function execute()
    {
        try {
            $warranty = $this->initWarranty();
            $warrantyData = $this->getRequest()->getPost('warranty');
            $orderId = $this->getRequest()->getPost('order');
            $warrantyData['leadToken'] = $this->getRequest()->getPost('leadToken');
            $quoteData =  $this->orderCreate->getQuote();

            $resultRedirect = $this->resultRedirectFactory->create();

            if (!$warranty) {
                $data = ["status"=>"fail"];
            }

            $orderInit = $this->orderRepository->get($orderId);
            $this->orderCreate->initFromOrder($orderInit);
            $this->_getSession()->setUseOldShippingMethod(true);
//            $items = $order->getQuote()->getItems();
//            foreach ($items as $item) {
//                $order->removeQuoteItem($item);
//            }

            $this->orderCreate->addProduct($warranty->getId(), $warrantyData);

            $data = ["status"=>"success"];

        } catch (\Exception $e) {
            $data = ["status"=>"fail"];
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setHttpResponseCode(200)->setData($data);
    }

}
