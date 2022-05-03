<?php

namespace Extend\Warranty\Model;

use Extend\Warranty\Model\Api\Request\OrderBuilder;
use Extend\Warranty\Model\Api\Sync\Orders\OrdersRequest;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Orders
{

    const CONTRACT = 'contract';
    const LEAD = 'lead';
    const LEAD_CONTRACT = 'lead_contract';

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
     * @param $orderMagento
     * @param $orderItem
     * @param $qtyInvoiced
     * @return string
     */
    public function createOrder($orderMagento, $orderItem, $qty, $type = self::CONTRACT) :string
    {
        $storeId = $orderItem->getStoreId();
        $apiUrl = $this->dataHelper->getApiUrl(ScopeInterface::SCOPE_STORES, $storeId);
        $apiStoreId = $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId);
        $apiKey = $this->dataHelper->getApiKey(ScopeInterface::SCOPE_STORES, $storeId);
        $orderExtend = '';
        try {
            $orderData = $this->orderBuilder->preparePayload($orderMagento, $orderItem, $qty, $type);
            $this->ordersRequest->setConfig($apiUrl, $apiStoreId, $apiKey);
            $response =  $this->ordersRequest->create($orderData, $type);
            if (!empty($response) && ($type == self::CONTRACT || $type == self::LEAD_CONTRACT)) {
                $orderExtend = $this->saveContract($orderItem, $qty, $response);
            } elseif (!empty($response) && $type == self::LEAD) {
                $orderExtend = $this->prepareLead($response);
            } elseif (empty($response) && $this->dataHelper->isContractCreateModeScheduled()) {
                $orderExtend = 'Scheduled';
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }

        return empty($orderExtend) ? '' : $orderExtend;
    }
    /**
     * @param $orderItem
     * @param $qty
     * @param $contractIds
     * @return string
     */
    private function saveContract($orderItem, $qty, $contractIds): string
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
     * @param $leadTokens
     * @return bool|string
     */
    private function prepareLead($leadTokens)
    {
        return $this->jsonSerializer->serialize($leadTokens);
    }
}
