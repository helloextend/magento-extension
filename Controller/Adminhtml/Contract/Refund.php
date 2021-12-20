<?php

namespace Extend\Warranty\Controller\Adminhtml\Contract;

use Extend\Warranty\Helper\Api\Data as ExtendData;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Extend\Warranty\Model\Api\Sync\Contract\ContractsRequest;
use Magento\Sales\Api\OrderItemRepositoryInterface;

class Refund extends Action
{
    const ADMIN_RESOURCE = 'Extend_Warranty::refund_warranty';

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var ContractsRequest
     */
    protected $contractsRequest;

    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var ExtendData
     */
    protected $extendHelper;

    public function __construct(
        Action\Context $context,
        ResultFactory $resultFactory,
        ContractsRequest $contractsRequest,
        OrderItemRepositoryInterface $orderItemRepository,
        ExtendData $extendHelper
    )
    {
        parent::__construct($context);

        $this->resultFactory = $resultFactory;
        $this->contractsRequest = $contractsRequest;
        $this->orderItemRepository = $orderItemRepository;
        $this->extendHelper = $extendHelper;
    }

    public function execute()
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        if (!$this->extendHelper->isExtendEnabled() || !$this->extendHelper->isRefundEnabled()) {
            $response->setData(
                [
                    'error' => 'Extend module or refunds are not enabled'
                ]
            );
            $response->setHttpResponseCode(403); //Forbidden error
            return $response;
        }

        $contractId = $this->getRequest()->getPost('contractId');

        $isValidationRequest = $this->getRequest()->getPost('validation');

        /* Validation Request */
        if (!empty($isValidationRequest) && $isValidationRequest) {
            $amountValidated = 0;
            foreach ($contractId as $_contractId) {
                $_response = $this->contractsRequest->validateRefund($_contractId);
                if (!empty($_response) && !empty($_response["refundAmount"])
                    && !empty($_response["refundAmount"]["amount"])) {
                    $amountValidated += $_response["refundAmount"]["amount"];
                }
            }

            //Cent to dollars
            if ($amountValidated > 0) {
                $amountValidated /= 100;
            }

            return $this->resultFactory->create(ResultFactory::TYPE_JSON)
                ->setHttpResponseCode(200)
                ->setData(["amountValidated" => $amountValidated]);
        }

        $itemId  = (string)$this->getRequest()->getPost('itemId');
        $item    = $this->orderItemRepository->get($itemId);
        $options = $item->getProductOptions();
        $response_log = empty($options['refund_responses_log']) ? [] : $options['refund_responses_log'];

        $currentContracts = json_decode($item->getContractId()) === NULL ?
            [$item->getContractId()] : json_decode($item->getContractId(), true);

        $refundHadErrors = false;

        foreach ($contractId as $_contractId) {
            $refundResponse = $this->contractsRequest->refund($_contractId);

            // Refunds log
            $response_log[] = [
                "contract_id" => $_contractId,
                "response" => $refundResponse
            ];

            if ($refundResponse == true) {
                if (($key = array_search($_contractId, $currentContracts)) !== false) {
                    unset($currentContracts[$key]);
                }
            } else {
                $refundHadErrors = true;
            }
        }

        //All contracts are refunded
        if (empty($currentContracts)) {
            $options['refund'] = true;
        } else {
            $options['refund'] = false;
        }

        //At least one error return 500 error code
        if ($refundHadErrors) {
            $response->setHttpResponseCode(500);
        } else {
            $response->setHttpResponseCode(200);
        }

        $options['refund_responses_log'] = $response_log;
        $item->setProductOptions($options);
        $item->setContractId(json_encode($currentContracts));
        $item->save();

        return $response;
    }
}