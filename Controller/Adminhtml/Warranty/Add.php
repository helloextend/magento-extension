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
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\ResponseInterface;

class Add extends Action
{
    public const ADMIN_RESOURCE = 'Extend_Warranty::warranty_admin_add';

    /**
     * Product Repository Model
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
     * Add constructor
     *
     * @param Action\Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SerializerInterface $serializer
     * @param OrderCreate $orderCreate
     */
    public function __construct(
        Action\Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SerializerInterface $serializer,
        OrderCreate $orderCreate
    ) {
        parent::__construct($context);

        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->serializer = $serializer;
        $this->orderCreate = $orderCreate;
    }

    /**
     * Init warranty
     *
     * @return ProductInterface
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
     * Add warranty product
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        try {
            $warranty = $this->initWarranty();
            $warrantyData = $this->getRequest()->getPost('warranty');
            $quoteData =  $this->orderCreate->getQuote();

            if (!$warranty) {
                $data = ["status"=>"fail"];
            }

            $this->orderCreate->addProduct($warranty->getId(), $warrantyData);
            $this->orderCreate->saveQuote();

            $data = ["status"=>"success"];

        } catch (\Exception $e) {
            $data = ["status"=>"fail"];
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setHttpResponseCode(200)->setData($data);
    }
}
