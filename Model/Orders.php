<?php

namespace Extend\Warranty\Model;

use Extend\Warranty\Model\Api\Request\OrderBuilder;
use Extend\Warranty\Model\Api\Sync\Orders\OrdersRequest;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Exception;

class Orders
{
    public const CONTRACT = 'contract';
    public const LEAD = 'lead';
    public const LEAD_CONTRACT = 'lead_contract';

    /**
     * @var OrdersRequest
     */
    protected $ordersRequest;

    /**
     * @var OrderBuilder
     */
    protected $orderBuilder;

    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param OrdersRequest $ordersRequest
     * @param OrderBuilder $orderBuilder
     * @param DataHelper $dataHelper
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param JsonSerializer $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrdersRequest $ordersRequest,
        OrderBuilder $orderBuilder,
        DataHelper $dataHelper,
        OrderItemRepositoryInterface $orderItemRepository,
        JsonSerializer $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->ordersRequest = $ordersRequest;
        $this->orderBuilder = $orderBuilder;
        $this->dataHelper = $dataHelper;
        $this->orderItemRepository = $orderItemRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * Create Order
     *
     * @param OrderInterface $orderMagento
     * @param OrderItemInterface $orderItem
     * @param int $qty
     * @param string|null $type
     * @return string
     *
     * @throws LocalizedException
     */
    public function createOrder(
        OrderInterface $orderMagento,
        OrderItemInterface $orderItem,
        int $qty,
        ?string $type = self::CONTRACT
    ) :string {
        $storeId = $orderItem->getStoreId();
        $apiUrl = $this->dataHelper->getApiUrl(ScopeInterface::SCOPE_STORES, $storeId);
        $apiStoreId = $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId);
        $apiKey = $this->dataHelper->getApiKey(ScopeInterface::SCOPE_STORES, $storeId);
        $orderExtend = '';
        $orderData = $this->orderBuilder->preparePayload($orderMagento, $orderItem, $qty, $type);
        try {
            $this->ordersRequest->setConfig($apiUrl, $apiStoreId, $apiKey);
            $response =  $this->ordersRequest->create($orderData, $type);
            if (!empty($response) && ($type == self::CONTRACT || $type == self::LEAD_CONTRACT)) {
                $orderExtend = $this->saveContract($orderItem, $qty, $response);
            } elseif (!empty($response) && $type == self::LEAD) {
                $orderExtend = $this->prepareLead($response);
            } elseif (empty($response) && $this->dataHelper->isContractCreateModeScheduled()) {
                $orderExtend = 'Scheduled';
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }

        return empty($orderExtend) ? '' : $orderExtend;
    }
    /**
     * Save Contract
     *
     * @param OrderItemInterface $orderItem
     * @param int $qty
     * @param array $contractIds
     * @return string
     */
    private function saveContract(OrderItemInterface $orderItem, int $qty, array $contractIds): string
    {
        $contractIdsJson = $this->jsonSerializer->serialize($contractIds);
        $orderItem->setContractId($contractIdsJson);
        $options = $orderItem->getProductOptions();
        $options['refund'] = false;
        $orderItem->setProductOptions($options);
        $this->orderItemRepository->save($orderItem);

        return count($contractIds) === $qty ? ContractCreate::STATUS_SUCCESS : ContractCreate::STATUS_PARTIAL;
    }

    /**
     * Prepare Lead
     *
     * @param array $leadTokens
     * @return bool|string
     */
    private function prepareLead(array $leadTokens)
    {
        return $this->jsonSerializer->serialize($leadTokens);
    }
}
