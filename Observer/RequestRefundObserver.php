<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

declare(strict_types=1);

namespace Extend\Warranty\Observer;

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Api\Sync\Contract\ContractsRequest as ApiContractModel;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Math\FloatComparator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class RequestRefundObserver
 */
class RequestRefundObserver implements ObserverInterface
{
    /**
     * API Contract Model
     *
     * @var ApiContractModel
     */
    private $apiContractModel;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Message Manager Interface
     *
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * Float Comparator
     *
     * @var FloatComparator
     */
    private $floatComparator;

    /**
     * Json Serializer
     *
     * @var Json
     */
    private $jsonSerializer;

    /**
     * Order Item Repository Interface
     *
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * LoggerInterface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * RequestRefundObserver constructor
     *
     * @param ApiContractModel $apiContractModel
     * @param DataHelper $dataHelper
     * @param MessageManagerInterface $messageManager
     * @param FloatComparator $floatComparator
     * @param Json $jsonSerializer
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ApiContractModel $apiContractModel,
        DataHelper $dataHelper,
        MessageManagerInterface $messageManager,
        FloatComparator $floatComparator,
        Json $jsonSerializer,
        OrderItemRepositoryInterface $orderItemRepository,
        LoggerInterface $logger
    ) {
        $this->apiContractModel = $apiContractModel;
        $this->dataHelper = $dataHelper;
        $this->messageManager = $messageManager;
        $this->floatComparator = $floatComparator;
        $this->jsonSerializer = $jsonSerializer;
        $this->orderItemRepository = $orderItemRepository;
        $this->logger = $logger;
    }

    /**
     * Validate a refund and report a contract cancellation
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (
            $this->dataHelper->isExtendEnabled()
            && $this->dataHelper->isRefundEnabled()
            && $this->dataHelper->isAutoRefundEnabled()
        ) {
            $event = $observer->getEvent();
            $creditmemo = $event->getCreditmemo();

            foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                $orderItem = $creditmemoItem->getOrderItem();
                $contractIds = $this->getContractIds($orderItem);

                if (!empty($contractIds)) {
                    $options['refund_responses_log'] = [];
                    $qtyRefunded = $creditmemoItem->getQty();

                    $refundedContractIds = array_slice($contractIds, 0, $qtyRefunded);

                    foreach ($refundedContractIds as $key => $contractId) {
                        $refundData = $this->apiContractModel->validateRefund($contractId);

                        if (
                            isset($refundData['refundAmount']['amount'])
                            && $this->floatComparator->greaterThan((float)$refundData['refundAmount']['amount'], 0)
                        ) {
                            $status = $this->apiContractModel->refund($contractId);
                            $options['refund_responses_log'][] = [
                                'contract_id' => $contractId,
                                'response' => $status,
                            ];

                            if ($status) {
                                unset($contractIds[$key]);
                            }
                        } else {
                            $this->messageManager->addErrorMessage(
                                __('Contract %1 can not be refunded.', $contractId)
                            );
                        }
                    }

                    if (!empty($options['refund_responses_log'])) {
                        try {
                            $contractIdsJson = $this->jsonSerializer->serialize($contractIds);
                            $orderItem->setContractId($contractIdsJson);
                            $options['refund'] = empty($contractIds);
                            $orderItem = $this->updateOrderItemOptions($orderItem, $options);
                            $this->orderItemRepository->save($orderItem);
                        } catch (LocalizedException $exception) {
                            $this->logger->error($exception->getMessage());
                        }
                    }
                }
            }
        }
    }

    /**
     * Get contract IDs
     *
     * @param OrderItemInterface $orderItem
     * @return array
     */
    protected function getContractIds(OrderItemInterface $orderItem): array
    {
        try {
            $contractIdsJson = $orderItem->getContractId();
            $contractIds = $contractIdsJson ? $this->jsonSerializer->unserialize($contractIdsJson) : [];
        } catch (LocalizedException $exception) {
            $contractIds = [];
        }

        return $contractIds;
    }

    /**
     * Update order item options
     *
     * @param OrderItemInterface $orderItem
     * @param array $productOptions
     * @return OrderItemInterface
     */
    protected function updateOrderItemOptions(OrderItemInterface $orderItem, array $productOptions): OrderItemInterface
    {
        $options = $orderItem->getProductOptions();
        $refundResponsesLog = $options['refund_responses_log'] ?? [];
        $refundResponsesLog = array_merge($refundResponsesLog, $productOptions['refund_responses_log']);
        $options['refund_responses_log'] = $refundResponsesLog;
        $options['refund'] = $productOptions['refund'];

        $orderItem->setProductOptions($options);

        return $orderItem;
    }
}
