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

use Extend\Warranty\Model\WarrantyContract as WarrantyContractModel;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Api\Sync\Contract\ContractsRequest as ApiContractModel;
use Extend\Warranty\Model\Api\Sync\Orders\RefundRequest as OrdersApiRefund;
use Extend\Warranty\Model\Config\Source\CreateContractApi;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Math\FloatComparator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\InvalidArgumentException;

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
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Warranty Contract Model
     *
     * @var WarrantyContractModel
     */
    private $warrantyContactModel;

    /**
     * Orders API refund Model
     *
     * @var OrdersApiRefund
     */
    private $ordersApiRefund;

    /**
     * Request observer constructor
     *
     * @param ApiContractModel $apiContractModel
     * @param DataHelper $dataHelper
     * @param MessageManagerInterface $messageManager
     * @param FloatComparator $floatComparator
     * @param Json $jsonSerializer
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param LoggerInterface $logger
     * @param WarrantyContractModel $warrantyContactModel
     * @param OrdersApiRefund $ordersApiRefund
     */
    public function __construct(
        ApiContractModel $apiContractModel,
        DataHelper $dataHelper,
        MessageManagerInterface $messageManager,
        FloatComparator $floatComparator,
        Json $jsonSerializer,
        OrderItemRepositoryInterface $orderItemRepository,
        LoggerInterface $logger,
        WarrantyContractModel $warrantyContactModel,
        OrdersApiRefund $ordersApiRefund
    ) {
        $this->apiContractModel = $apiContractModel;
        $this->dataHelper = $dataHelper;
        $this->messageManager = $messageManager;
        $this->floatComparator = $floatComparator;
        $this->jsonSerializer = $jsonSerializer;
        $this->orderItemRepository = $orderItemRepository;
        $this->logger = $logger;
        $this->warrantyContactModel = $warrantyContactModel;
        $this->ordersApiRefund = $ordersApiRefund;
    }

    /**
     * Validate a refund and report a contract cancellation
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $creditmemo = $event->getCreditmemo();
        $order = $creditmemo->getOrder();
        $storeId = $order->getStoreId();

        if (
            $this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId)
            && $this->dataHelper->isRefundEnabled($storeId)
            && $this->dataHelper->isAutoRefundEnabled($storeId)
        ) {
            $event = $observer->getEvent();
            $creditmemo = $event->getCreditmemo();

            foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                $orderItem = $creditmemoItem->getOrderItem();
                $contractIds = $this->warrantyContactModel->getContractIds($orderItem);

                if (!empty($contractIds)) {
                    $apiUrl = $this->dataHelper->getApiUrl(ScopeInterface::SCOPE_STORES, $storeId);
                    $apiStoreId = $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId);
                    $apiKey = $this->dataHelper->getApiKey(ScopeInterface::SCOPE_STORES, $storeId);

                    $options['refund_responses_log'] = [];
                    $qtyRefunded = $creditmemoItem->getQty();
                    $validContracts = [];

                    $refundedContractIds = array_slice($contractIds, 0, $qtyRefunded);
                    try {
                        if ($this->dataHelper->getContractCreateApi(ScopeInterface::SCOPE_STORES, $storeId) == CreateContractApi::CONTACTS_API) {
                            $this->apiContractModel->setConfig($apiUrl, $apiStoreId, $apiKey);
                        } elseif ($this->dataHelper->getContractCreateApi(ScopeInterface::SCOPE_STORES, $storeId) == CreateContractApi::ORDERS_API) {
                            $this->ordersApiRefund->setConfig($apiUrl, $apiStoreId, $apiKey);
                        }

                        foreach ($refundedContractIds as $key => $contractId) {
                            $isValidRefund = $this->validateRefund($contractId, $storeId);

                            if ($isValidRefund) {
                                $validContracts[$key] = $contractId;
                            } else {
                                $this->messageManager->addErrorMessage(
                                    __('Contract %1 can not be refunded.', $contractId)
                                );
                            }
                        }

                        foreach ($validContracts as $key => $contractId) {
                            if ($this->dataHelper->getContractCreateApi(ScopeInterface::SCOPE_STORES, $storeId) == CreateContractApi::CONTACTS_API) {
                                $status = $this->apiContractModel->refund($contractId);
                            } elseif ($this->dataHelper->getContractCreateApi(ScopeInterface::SCOPE_STORES, $storeId) == CreateContractApi::ORDERS_API) {
                                $status = $this->ordersApiRefund->refund($contractId);
                            }

                            $options['refund_responses_log'][] = [
                                'contract_id' => $contractId,
                                'response' => $status,
                            ];

                            if ($status) {
                                unset($contractIds[$key]);
                            }
                        }
                    } catch (InvalidArgumentException $exception) {
                        $this->logger->error($exception->getMessage());
                    }

                    if (!empty($options['refund_responses_log'])) {
                        try {
                            $contractIdsJson = $this->jsonSerializer->serialize($contractIds);
                            $orderItem->setContractId($contractIdsJson);
                            $options['refund'] = empty($contractIds);
                            $orderItem = $this->warrantyContactModel->updateOrderItemOptions($orderItem, $options);
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
     * Validate refund
     *
     * @param string $contractId
     * @param $storeId
     * @return bool
     */
    private function validateRefund(string $contractId, $storeId): bool
    {
        $isValid = false;

        if ($this->dataHelper->getContractCreateApi(ScopeInterface::SCOPE_STORES, $storeId) == CreateContractApi::CONTACTS_API) {
            $refundData = $this->apiContractModel->validateRefund($contractId);
            if (
                isset($refundData['refundAmount']['amount'])
                && $this->floatComparator->greaterThan((float)$refundData['refundAmount']['amount'], 0)
            ) {
                $isValid = true;
            }
        } elseif ($this->dataHelper->getContractCreateApi(ScopeInterface::SCOPE_STORES, $storeId) == CreateContractApi::ORDERS_API) {
            $refundData = $this->ordersApiRefund->validateRefund($contractId);
            if (
                isset($refundData['refundAmounts']['customer'])
                && $this->floatComparator->greaterThan((float)$refundData['refundAmounts']['customer'], 0)
            ) {
                $isValid = true;
            }
        }

        return $isValid;
    }
}
