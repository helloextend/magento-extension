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
        $response_log = [];
        $noErrors = true;

        foreach ($contractId as $_contractId) {
            $refundResponse = $this->contractsRequest->refund($_contractId);
            // Responses log
            $response_log[] = [
                "contract_id" => $_contractId,
                "response" => $refundResponse
            ];

            //At least one error
            if ($refundResponse == false) {
                $noErrors = false;
            }
        }

        $itemId = (string)$this->getRequest()->getPost('itemId');
        $item = $this->orderItemRepository->get($itemId);
        $options = $item->getProductOptions();

        if ($noErrors) {
            $options['refund'] = true;
            $options['refund_responses_log'] = $response_log;
            $item->setProductOptions($options);
            $item->save();
            $response->setHttpResponseCode(200);
        } else {
            $options['refund_responses_log'] = $response_log;
            $item->setProductOptions($options);
            $item->save();
            $response->setHttpResponseCode(500);
        }

        return $response;
    }
}