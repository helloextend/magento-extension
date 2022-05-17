<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model;

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Framework\Exception\LocalizedException;
use Extend\Warranty\Helper\FloatComparator;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Extend\Warranty\Model\Api\Sync\Contract\ContractsRequest as ApiContractModel;
use Extend\Warranty\Model\Api\Request\ContractBuilder as ContractPayloadBuilder;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Class WarrantyContract
 *
 * Warranty Contract Model
 */
class WarrantyContract
{
    public const CONTRACT = 'contract';

    public const LEAD_CONTRACT = 'lead_contract';

    /**
     * API Contract
     *
     * @var ApiContractModel
     */
    private $apiContractModel;

    /**
     * Contract Payload Builder Model
     *
     * @var ContractPayloadBuilder
     */
    private $contractPayloadBuilder;

    /**
     * Json Serializer Model
     *
     * @var JsonSerializer
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
     * Float Comparator Model
     *
     * @var FloatComparator
     */
    private $floatComparator;

    /**
     * Warranty Api DataHelper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Contracts constructor
     *
     * @param ApiContractModel $apiContractModel
     * @param ContractPayloadBuilder $contractPayloadBuilder
     * @param JsonSerializer $jsonSerializer
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param LoggerInterface $logger
     * @param FloatComparator $floatComparator
     * @param DataHelper $dataHelper
     */
    public function __construct(
        ApiContractModel $apiContractModel,
        ContractPayloadBuilder $contractPayloadBuilder,
        JsonSerializer $jsonSerializer,
        OrderItemRepositoryInterface $orderItemRepository,
        LoggerInterface $logger,
        FloatComparator $floatComparator,
        DataHelper $dataHelper
    ) {
        $this->apiContractModel = $apiContractModel;
        $this->contractPayloadBuilder = $contractPayloadBuilder;
        $this->jsonSerializer = $jsonSerializer;
        $this->orderItemRepository = $orderItemRepository;
        $this->logger = $logger;
        $this->floatComparator = $floatComparator;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Create a warranty contract
     *
     * @param OrderInterface $order
     * @param OrderItemInterface $orderItem
     * @param int $qtyInvoiced
     * @param string|null $type
     *
     * @return string
     *
     * @throws LocalizedException
     */
    public function create(
        OrderInterface $order,
        OrderItemInterface $orderItem,
        int $qtyInvoiced,
        ?string $type = self::CONTRACT
    ): string {
        $result = ContractCreate::STATUS_FAILED;

        if (!$this->canCreateWarranty($orderItem)) {
            $this->logger->error('Warranty is already created for order item ID ' . $orderItem->getItemId());
            return $result;
        }

        $contractPayload = $this->contractPayloadBuilder->preparePayload($order, $orderItem, $type);

        if (!empty($contractPayload)) {
            $storeId = $orderItem->getStoreId();
            $apiUrl = $this->dataHelper->getApiUrl(ScopeInterface::SCOPE_STORES, $storeId);
            $apiStoreId = $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId);
            $apiKey = $this->dataHelper->getApiKey(ScopeInterface::SCOPE_STORES, $storeId);

            $this->apiContractModel->setConfig($apiUrl, $apiStoreId, $apiKey);

            $newContractIds = [];
            $qty = 1;
            do {
                $contractId = $this->apiContractModel->create($contractPayload);
                if ($contractId) {
                    $timePrefix = uniqid();
                    $newContractIds[$timePrefix] = $contractId;
                }
                $qty++;
            } while ($qty <= $qtyInvoiced);

            if (!empty($newContractIds)) {
                $contractIds = array_merge(
                    $this->getContractIds($orderItem),
                    $newContractIds
                );
                try {
                    $contractIdsJson = $this->jsonSerializer->serialize($contractIds);
                } catch (Exception $exception) {
                    $this->logger->error($exception->getMessage());
                }
                $orderItem->setContractId($contractIdsJson);

                $options = $orderItem->getProductOptions();
                $options['refund'] = false;
                $orderItem->setProductOptions($options);

                try {
                    $this->orderItemRepository->save($orderItem);
                    $result = count($newContractIds) === $qtyInvoiced ? ContractCreate::STATUS_SUCCESS :
                        ContractCreate::STATUS_PARTIAL;
                } catch (Exception $exception) {
                    $this->logger->error($exception->getMessage());
                }
            }
        }

        return $result;
    }

    /**
     * Get warranty contract IDs
     *
     * @param OrderItemInterface $orderItem
     * @return array
     */
    public function getContractIds(OrderItemInterface $orderItem): array
    {
        try {
            $contractIdsJson = $orderItem->getContractId();
            $contractIds = $contractIdsJson ? $this->jsonSerializer->unserialize($contractIdsJson) : [];
        } catch (Exception $exception) {
            $contractIds = [];
        }

        return $contractIds;
    }

    /**
     * Check if warranty can be created
     *
     * @param OrderItemInterface $orderItem
     * @return bool
     */
    protected function canCreateWarranty(OrderItemInterface $orderItem): bool
    {
        $qtyInvoiced = (float)$orderItem->getQtyInvoiced();

        $options = $orderItem->getProductOptions();
        $refundResponsesLogEntries = $options['refund_responses_log'] ?? [];
        $contractIds = $this->getContractIds($orderItem);

        $warrantyQty = count($contractIds) + count($refundResponsesLogEntries);

        return $this->floatComparator->greaterThan($qtyInvoiced, (float)$warrantyQty);
    }

    /**
     * Update order item options
     *
     * @param OrderItemInterface $orderItem
     * @param array $productOptions
     * @return OrderItemInterface
     */
    public function updateOrderItemOptions(OrderItemInterface $orderItem, array $productOptions): OrderItemInterface
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
