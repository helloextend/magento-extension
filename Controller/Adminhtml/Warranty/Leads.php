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

    /**
     * @var OrderRepository
     */
    protected $orderRepository;


    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

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
        QuoteManagement $quoteManagement
    ) {
        parent::__construct($context);

        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->serializer = $serializer;
        $this->orderCreate = $orderCreate;
        $this->orderRepository = $orderRepository;
        $this->quoteManagement = $quoteManagement;
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
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $warranty = $this->initWarranty();
            $warrantyData = $this->getRequest()->getPost('warranty');
            $orderId = $this->getRequest()->getPost('order');
            $warrantyData['leadToken'] = $this->getRequest()->getPost('leadToken');

            if (!$warranty) {
                $data = ["status"=>"fail"];
            }

            $this->_getSession()->setUseOldShippingMethod(true);

            $orderInit = $this->orderRepository->get($orderId);
            $this->orderCreate->initFromOrder($orderInit);
            $this->orderCreate->getQuote()->removeAllItems();
            $this->orderCreate->addProduct($warranty->getId(), $warrantyData);
            $this->orderCreate->setInventoryProcessed(false);
            $this->orderCreate->setPaymentMethod('checkmo');
            $this->orderCreate->recollectCart();
            $this->orderCreate->saveQuote();

            $quote = $this->orderCreate->getQuote();

            $order = $this->quoteManagement->submit($quote);

            $data = ["status"=>"success", "redirect" => $this->_url->getUrl('sales/order/view/', ['order_id' => $order->getId()]) ];

        } catch (\Exception $e) {
            $data = ["status"=>"fail"];
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setHttpResponseCode(200)->setData($data);
    }

}
