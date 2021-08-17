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

namespace Extend\Warranty\Model;

use Magento\Framework\Math\FloatComparator;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Extend\Warranty\Model\Api\Sync\Contract\ContractsRequest as ApiContractModel;
use Extend\Warranty\Model\Api\Request\ContractBuilder as ContractPayloadBuilder;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class WarrantyContract
 */
class WarrantyContract
{
    /**
     * API Contract Model
     *
     * @var ApiContractModel
     */
    private $apiContractModel;

    /**
     * Contract Payload Builder
     *
     * @var ContractPayloadBuilder
     */
    private $contractPayloadBuilder;

    /**
     * Json Serializer
     *
     * @var JsonSerializer
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
     * Float Comparator
     *
     * @var FloatComparator
     */
    private $floatComparator;

    /**
     * WarrantyContract constructor
     *
     * @param ApiContractModel $apiContractModel
     * @param ContractPayloadBuilder $contractPayloadBuilder
     * @param JsonSerializer $jsonSerializer
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param LoggerInterface $logger
     * @param FloatComparator $floatComparator
     */
    public function __construct(
        ApiContractModel $apiContractModel,
        ContractPayloadBuilder $contractPayloadBuilder,
        JsonSerializer $jsonSerializer,
        OrderItemRepositoryInterface $orderItemRepository,
        LoggerInterface $logger,
        FloatComparator $floatComparator
    ) {
        $this->apiContractModel = $apiContractModel;
        $this->contractPayloadBuilder = $contractPayloadBuilder;
        $this->jsonSerializer = $jsonSerializer;
        $this->orderItemRepository = $orderItemRepository;
        $this->logger = $logger;
        $this->floatComparator = $floatComparator;
    }

    /**
     * Create a warranty contract
     *
     * @param OrderInterface $order
     * @param OrderItemInterface $orderItem
     * @param int $qtyInvoiced
     * @return string
     * @throws NoSuchEntityException
     */
    public function create(OrderInterface $order, OrderItemInterface $orderItem, int $qtyInvoiced): string
    {
        $result = ContractCreate::STATUS_FAILED;

        if (!$this->canCreateWarranty($orderItem)) {
            $this->logger->error('Warranty is already created for order item ID ' . $orderItem->getItemId());
            return $result;
        }

        $contractPayload = $this->contractPayloadBuilder->preparePayload($order, $orderItem);

        if (!empty($contractPayload)) {
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
                $contractIdsJson = $this->jsonSerializer->serialize($contractIds);
                $orderItem->setContractId($contractIdsJson);

                $options = $orderItem->getProductOptions();
                $options['refund'] = false;
                $orderItem->setProductOptions($options);

                try {
                    $this->orderItemRepository->save($orderItem);
                    $result = count($newContractIds) === $qtyInvoiced ? ContractCreate::STATUS_SUCCESS : ContractCreate::STATUS_PARTIAL;
                } catch (LocalizedException $exception) {
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
}
