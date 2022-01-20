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

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Extend\Warranty\Model\Api\Request\OrderBuilder as ExtendOrderBuilder;
use Extend\Warranty\Model\Api\Sync\Orders\OrdersRequest as ExtendOrderApiRequest;
use Extend\Warranty\Model\Api\Sync\Orders\HistoricalOrdersRequest;
use Extend\Warranty\Helper\Api\Data as DataHelper;

class Orders
{

    /**
     * Extend Orders API request type
     */
    const CONTRACT = 'contract';
    const LEAD = 'lead';
    const LEAD_CONTRACT = 'lead_contract';
    const BATCH = 'batch';

    /**
     * @var ExtendOrderApiRequest
     */
    protected $extendOrderApiRequest;

    /**
     * @var HistoricalOrdersRequest
     */
    protected $historicalOrdersRequest;

    /**
     * @var ExtendOrderBuilder
     */
    protected $extendOrderBuilder;

    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

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
     * @var Date
     */
    protected $date;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @param ExtendOrderApiRequest $extendOrderApiRequest
     * @param HistoricalOrdersRequest $historicalOrdersRequest
     * @param ExtendOrderBuilder $extendOrderBuilder
     * @param DataHelper $dataHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param JsonSerializer $jsonSerializer
     * @param LoggerInterface $logger
     * @param Date $date
     * @param DateTime $dateTime
     */
    public function __construct
    (
        ExtendOrderApiRequest        $extendOrderApiRequest,
        HistoricalOrdersRequest      $historicalOrdersRequest,
        ExtendOrderBuilder           $extendOrderBuilder,
        DataHelper                   $dataHelper,
        SearchCriteriaBuilder        $searchCriteriaBuilder,
        OrderRepositoryInterface     $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        JsonSerializer               $jsonSerializer,
        LoggerInterface              $logger,
        Date                         $date,
        DateTime                     $dateTime
    ) {
        $this->extendOrderApiRequest = $extendOrderApiRequest;
        $this->historicalOrdersRequest = $historicalOrdersRequest;
        $this->extendOrderBuilder = $extendOrderBuilder;
        $this->dataHelper = $dataHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->date = $date;
        $this->dateTime = $dateTime;
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
            $orderData = $this->extendOrderBuilder->preparePayload($orderMagento, $orderItem, $qty, $type);
            $this->extendOrderApiRequest->setConfig($apiUrl,$apiStoreId,$apiKey);
            $response =  $this->extendOrderApiRequest->create($orderData, $type);
            if (!empty($response) && ($type == self::CONTRACT || $type == self::LEAD_CONTRACT)) {
                $orderExtend = $this->saveContract($orderItem, $qty, $response);
            } elseif(!empty($response) && $type == self::LEAD) {
                $orderExtend = $this->prepareLead($response);
            } elseif (empty($response) && $this->dataHelper->getOrdersApiCreateMode()) {
                $orderExtend = 'Scheduled';
            }
        } catch(\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }

        return empty($orderExtend) ? '' : $orderExtend;
    }

    public function createOrderBatch()
    {
        $apiUrl = $this->dataHelper->getApiUrl(ScopeInterface::SCOPE_STORES, Store::DEFAULT_STORE_ID);
        $apiStoreId = $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, Store::DEFAULT_STORE_ID);
        $apiKey = $this->dataHelper->getApiKey(ScopeInterface::SCOPE_STORES, Store::DEFAULT_STORE_ID);

        try {
            $this->historicalOrdersRequest->setConfig($apiUrl,$apiStoreId,$apiKey);

            $offset = 60*60*24*30*12*2; // 2 Years
            $dateTo = $this->dateTime->formatDate($this->date->gmtTimestamp());
            $dateFrom = $this->dateTime->formatDate($this->date->gmtTimestamp() - $offset);;

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('created_at', $dateFrom, 'gteq')
                ->addFilter('created_at', $dateTo, 'lteq')
                ->create();
            $orders = $this->orderRepository->getList($searchCriteria)->getItems();

            $ordersData = $this->extendOrderBuilder->preparePayloadBatch($orders);
            $this->historicalOrdersRequest->create($ordersData);
        } catch (\Exception $e) {

        }
    }

    /**
     * @param $orderItem
     * @param $qty
     * @param $contractIds
     * @return string
     */
    private function saveContract($orderItem, $qty, $contractIds): string
    {
        if (!$this->dataHelper->getOrdersApiCreateMode()) {
            $contractIdsJson = $this->jsonSerializer->serialize($contractIds);
            $orderItem->setContractId($contractIdsJson);
            $options = $orderItem->getProductOptions();
            $options['refund'] = false;
            $orderItem->setProductOptions($options);
            $this->orderItemRepository->save($orderItem);

            return count($contractIds) === $qty ? ContractCreate::STATUS_SUCCESS : ContractCreate::STATUS_PARTIAL;
        }

        return ContractCreate::STATUS_FAILED;
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
