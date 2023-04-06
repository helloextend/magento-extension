<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

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
use Extend\Warranty\Helper\FloatComparator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Class RequestRefundObserver
 *
 * RequestRefundObserver Observer
 */
class RequestRefundObserver implements ObserverInterface
{
    /**
     * API Contract
     *
     * @var ApiContractModel
     */
    private $apiContractModel;

    /**
     * Warranty Api Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Message Manager Model
     *
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * Float Comparator Model
     *
     * @var FloatComparator
     */
    private $floatComparator;

    /**
     * Json Serializer Model
     *
     * @var Json
     */
    private $jsonSerializer;

    /**
     * Order Item Repository Model
     *
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * Logger Model
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Warranty Contract
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
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $creditmemo = $event->getCreditmemo();
        $order = $creditmemo->getOrder();
        $storeId = $order->getStoreId();

        if ($this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId)
            && $this->dataHelper->isRefundEnabled($storeId)
            && $this->dataHelper->isAutoRefundEnabled($storeId)
        ) {
            $apiUrl = $this->dataHelper->getApiUrl(ScopeInterface::SCOPE_STORES, $storeId);
            $apiStoreId = $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId);
            $apiKey = $this->dataHelper->getApiKey(ScopeInterface::SCOPE_STORES, $storeId);
            $refundItems = [];

            foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                $orderItem = $creditmemoItem->getOrderItem();
                $contractIds = $this->warrantyContactModel->getContractIds($orderItem);

                if (!empty($contractIds)) {
                    $qtyRefunded = $creditmemoItem->getQty();
                    $refundedContractIds = array_slice($contractIds, 0, $qtyRefunded);
                    $refundItems[$orderItem->getId()] = $refundedContractIds;
                }
            }

            try {
                $validContracts = [];
                $refundedContracts = [];

                if (!empty($refundItems)) {
                    try {
                        $contractCreateApi =
                            $this->dataHelper->getContractCreateApi(ScopeInterface::SCOPE_STORES, $storeId);
                        if ($contractCreateApi == CreateContractApi::ORDERS_API) {
                            $this->ordersApiRefund->setConfig($apiUrl, $apiStoreId, $apiKey);
                        }

                        $validContracts = $this->validateRefund($refundItems, $storeId);
                    } catch (Exception $exception) {
                        $this->logger->error($exception->getMessage());
                    }
                }

                if (!empty($validContracts)) {
                    $refundedContracts = $this->refund($validContracts, $storeId);
                }

                if (!empty($refundedContracts)) {
                    foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                        $orderItem = $creditmemoItem->getOrderItem();
                        $contractIds = $this->warrantyContactModel->getContractIds($orderItem);

                        if (!array_key_exists($orderItem->getId(), $refundedContracts)) {
                            continue;
                        }

                        foreach ($refundedContracts as $refundedContractsData) {
                            $contractIds = array_diff($contractIds, $refundedContractsData['contractIds']);
                            $contractIdsJson = $this->jsonSerializer->serialize($contractIds);
                            $options = $refundedContractsData['options'];
                            $orderItem->setContractId($contractIdsJson);
                            $options['refund'] = empty($contractIds);
                            $orderItem = $this->warrantyContactModel->updateOrderItemOptions($orderItem, $options);
                            $this->orderItemRepository->save($orderItem);
                        }
                    }
                }
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }

    /**
     * Validate refund
     *
     * @param array $refundItems
     * @param string|int|null $storeId
     * @return array
     */
    private function validateRefund(array $refundItems, $storeId): array
    {
        $validContracts = [];

        foreach ($refundItems as $itemId => $item) {
            foreach ($item as $key => $contractId) {
                if ($this->dataHelper->getContractCreateApi(ScopeInterface::SCOPE_STORES, $storeId) ==
                    CreateContractApi::ORDERS_API
                ) {
                    $refundData = $this->ordersApiRefund->validateRefund($contractId);
                    if (isset($refundData['refundAmounts'])
                        && isset($refundData['refundAmounts']['customer'])
                        && $this->floatComparator->greaterThan(
                            (float)$refundData['refundAmounts']['customer']
                            , 0
                        )
                    ) {
                        $validContracts[$itemId][$key] = $contractId;
                    }
                } else {
                    $this->messageManager->addErrorMessage(
                        __('Contract %1 can not be refunded.', $contractId)
                    );
                }
            }
        }

        return $validContracts;
    }

    /**
     * Request a refund
     *
     * @param array $validContracts
     * @param string|int|null $storeId
     * @return array
     */
    private function refund(array $validContracts, $storeId): array
    {
        $status = false;
        $refundedContracts = [];
        $options['refund_responses_log'] = [];

        foreach ($validContracts as $itemId => $item) {
            foreach ($item as $key => $contractId) {
               if ($this->dataHelper->getContractCreateApi(ScopeInterface::SCOPE_STORES, $storeId) ==
                    CreateContractApi::ORDERS_API
                ) {
                    $status = $this->ordersApiRefund->refund($contractId);
                }

                if ($status) {
                    $options['refund_responses_log'][] = [
                        'contract_id' => $contractId,
                        'response' => $status,
                    ];
                    $refundedContracts[$itemId]['contractIds'][] = $contractId;
                    $refundedContracts[$itemId]['options'] = $options;
                }
            }
        }

        return $refundedContracts;
    }
}
