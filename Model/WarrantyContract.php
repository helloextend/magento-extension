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

use Magento\Sales\Model\Order;
use Extend\Warranty\Helper\Data;
use Extend\Warranty\Model\Api\Sync\Contract\ContractsRequest;
use Extend\Warranty\Model\Api\Request\ContractBuilder as ContractPayloadBuilder;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class WarrantyContract
 */
class WarrantyContract
{
    /**
     * Contracts Request
     *
     * @var ContractsRequest
     */
    private $contractsRequest;

    /**
     * Contract Payload Builder
     *
     * @var ContractPayloadBuilder
     */
    private $contractPayloadBuilder;

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
     * WarrantyContract constructor
     *
     * @param ContractsRequest $contractsRequest
     * @param ContractPayloadBuilder $contractPayloadBuilder
     * @param Json $jsonSerializer
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ContractsRequest $contractsRequest,
        ContractPayloadBuilder $contractPayloadBuilder,
        Json $jsonSerializer,
        OrderItemRepositoryInterface $orderItemRepository,
        LoggerInterface $logger
    ) {
        $this->contractsRequest = $contractsRequest;
        $this->contractPayloadBuilder = $contractPayloadBuilder;
        $this->jsonSerializer = $jsonSerializer;
        $this->orderItemRepository = $orderItemRepository;
        $this->logger = $logger;
    }

    /**
     * Create a warranty contract
     *
     * @param Order $order
     * @param array $warranties
     */
    public function createContract(Order $order, array $warranties): void
    {
        try {
            $contracts = $this->contractPayloadBuilder->prepareInfo($order, $warranties);

            foreach ($contracts as $key => $contract) {
                if (isset($contract['product']['qty'])) {
                    $contractIds = [];
                    $qty = $contract['product']['qty'];

                    for ($i = 1; $i <= $qty; $i++) {
                        $contractId = $this->contractsRequest->create($contract);
                        if ($contractId) {
                            $contractIds[$i] = $contractId;
                        }
                    }

                    if (!empty($contractIds)) {
                        $contractIdsJson = $this->jsonSerializer->serialize($contractIds);

                        foreach ($order->getAllItems() as $orderItem) {
                            $orderItemId = (int)$orderItem->getId();
                            if ($orderItemId === $key && !$orderItem->getData(Data::CONTRACT_ID)) {
                                $orderItem->setData(Data::CONTRACT_ID, $contractIdsJson);
                                $options = $orderItem->getProductOptions();
                                $options['refund'] = false;
                                $orderItem->setProductOptions($options);

                                $this->orderItemRepository->save($orderItem);
                            }
                        }
                    }
                }
            }
        } catch (LocalizedException $exception) {
            $this->logger->error('Error during warranty contract creation. ' . $exception->getMessage());
        }
    }
}
