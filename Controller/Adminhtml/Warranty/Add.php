<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Controller\Adminhtml\Warranty;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\AdminOrder\Create as OrderCreate;
use Extend\Warranty\Model\Product\Type as WarrantyType;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\ResponseInterface;
use Psr\Log\LoggerInterface;
use Extend\Warranty\Model\Offers as OfferModel;

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
     * Logger Model
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OfferModel
     */
    protected $offerModel;

    /**
     * Add constructor
     *
     * @param Action\Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SerializerInterface $serializer
     * @param OrderCreate $orderCreate
     * @param OfferModel $offerModel
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context             $context,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder      $searchCriteriaBuilder,
        SerializerInterface        $serializer,
        OrderCreate                $orderCreate,
        OfferModel                 $offerModel,
        LoggerInterface            $logger
    )
    {
        parent::__construct($context);

        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->serializer = $serializer;
        $this->orderCreate = $orderCreate;
        $this->logger = $logger;
        $this->offerModel = $offerModel;
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
        $searchResults = $this->productRepository->getList($searchCriteria);
        $results = $searchResults->getItems();

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

            $errors = $this->offerModel->validateWarranty($warrantyData);

            if (!empty($errors)) {
                $errorsAsString = implode(' ', $errors);
                $this->logger->error(
                    'Invalid warranty data. ' . $errorsAsString . ' Warranty data: ' .
                    $this->offerModel->getWarrantyDataAsString($warrantyData)
                );

                $errorMessage = 'Invalid warranty data. Please Check logs';

                $responseData = [
                    'status' => false,
                    'error' => $errorMessage,
                ];

                return $this->jsonResponse($responseData);
            }

            $this->orderCreate->addProduct($warranty->getId(), $warrantyData);
            $this->orderCreate->saveQuote();

            $data = ["status" => "success"];

        } catch (\Exception $e) {
            $errorMessage = "Something gone wrong.<br/>" .
                "Error: " . $e->getMessage() . '<br/>' .
                'Please check extend logs for more details.';

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
        $resultJson = $this->resultFactory
            ->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);

        return $resultJson;
    }
}
