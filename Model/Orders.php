<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model;

use Extend\Warranty\Model\Api\Request\FullOrderBuilder;
use Extend\Warranty\Model\Api\Request\LineItem\AbstractLineItemBuilder;
use Extend\Warranty\Model\ExtendOrderFactory;
use Extend\Warranty\Model\Api\Response\OrderResponse;
use Extend\Warranty\Model\Api\Sync\LineItem\FulfillRequest;
use Extend\Warranty\Model\Api\Sync\LineItem\LineItemSearch;
use Extend\Warranty\Model\Api\Sync\Orders\OrdersRequest;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
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
     * @var FulfillRequest
     */
    protected $fulfillRequest;

    /**
     * @var FullOrderBuilder
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
     * @var LineItemSearch
     */
    protected $lineItemSearch;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Extend\Warranty\Model\ExtendOrderFactory
     */
    protected $extendOrderFactory;

    /**
     * @var ExtendOrderRepository
     */
    protected $extendOrderRepository;

    /**
     * @param OrdersRequest $ordersRequest
     * @param FullOrderBuilder $orderBuilder
     * @param DataHelper $dataHelper
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param JsonSerializer $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrdersRequest                $ordersRequest,
        ExtendOrderRepository        $extendOrderRepository,
        FulfillRequest               $fulfillRequest,
        LineItemSearch               $lineItemSearch,
        FullOrderBuilder             $orderBuilder,
        DataHelper                   $dataHelper,
        OrderItemRepositoryInterface $orderItemRepository,
        JsonSerializer               $jsonSerializer,
        ExtendOrderFactory           $extendOrderFactory,
        LoggerInterface              $logger
    )
    {
        $this->ordersRequest = $ordersRequest;
        $this->orderBuilder = $orderBuilder;
        $this->dataHelper = $dataHelper;
        $this->orderItemRepository = $orderItemRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->fulfillRequest = $fulfillRequest;
        $this->lineItemSearch = $lineItemSearch;
        $this->extendOrderFactory = $extendOrderFactory;
        $this->extendOrderRepository = $extendOrderRepository;
    }


    /**
     * @param $storeId
     * @return OrdersRequest
     * @throws LocalizedException
     */
    protected function getOrderRequest($storeId)
    {
        $apiUrl = $this->dataHelper->getApiUrl(ScopeInterface::SCOPE_STORES, $storeId);
        $apiStoreId = $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId);
        $apiKey = $this->dataHelper->getApiKey(ScopeInterface::SCOPE_STORES, $storeId);

        $this->ordersRequest->setConfig($apiUrl, $apiStoreId, $apiKey);
        return $this->ordersRequest;
    }

    /**
     * @param $storeId
     * @return FulfillRequest
     * @throws LocalizedException
     */
    protected function getFulfillRequest($storeId)
    {
        $apiUrl = $this->dataHelper->getApiUrl(ScopeInterface::SCOPE_STORES, $storeId);
        $apiStoreId = $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId);
        $apiKey = $this->dataHelper->getApiKey(ScopeInterface::SCOPE_STORES, $storeId);

        $this->fulfillRequest->setConfig($apiUrl, $apiStoreId, $apiKey);
        return $this->fulfillRequest;
    }

    /**
     * @param $storeId
     * @return LineItemSearch
     * @throws LocalizedException
     */
    protected function getLineItemSearch($storeId)
    {
        $apiUrl = $this->dataHelper->getApiUrl(ScopeInterface::SCOPE_STORES, $storeId);
        $apiStoreId = $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId);
        $apiKey = $this->dataHelper->getApiKey(ScopeInterface::SCOPE_STORES, $storeId);

        $this->lineItemSearch->setConfig($apiUrl, $apiStoreId, $apiKey);
        return $this->lineItemSearch;
    }

    /**
     * @param OrderInterface $order
     * @return void
     * @throws LocalizedException
     */
    public function create($order)
    {
        $storeId = $order->getStoreId();

        try {
            $orderData = $this->orderBuilder->preparePayload($order);

            /** @var OrderResponse $orderResponse */
            $orderResponse = $this->getOrderRequest($storeId)
                ->create($orderData);

            /** @var ExtendOrder $extendOrder */
            $extendOrder = $this->extendOrderFactory->create();
            $extendOrder->setOrderId($order->getEntityId());
            $extendOrder->setExtendOrderId($orderResponse->getId());

            $this->extendOrderRepository->save($extendOrder);

            foreach ($order->getItems() as $orderItem) {
                $lineItems = $orderResponse->getLineItemsByOrderItem($orderItem);

                $leads = $this->collectLeads($lineItems);
                $contracts = $this->collectContracts($lineItems);

                if (!empty($leads)) {
                    $leadTokens = $this->prepareLead($leads);
                    $orderItem->setLeadToken($leadTokens);

                    /**
                     * fixes magento issue with storing entities in registry by entity_id
                     * in some cases entity_id is null and ItemRepository::get($orderItemId)
                     * returns wrong entity. So setting entity_id manually
                     * so registry will have correct entity with contract id.
                     *
                     * without it contract id can be cleared on next save method call
                     */

                    $orderItem->setEntityId($orderItem->getId());
                    $this->orderItemRepository->save($orderItem);
                }

                if (!empty($contracts)) {
                    $this->saveContract(
                        $orderItem,
                        $contracts
                    );
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            throw new LocalizedException(new Phrase('Order API contract create error'), $e);
        }
    }

    /**
     * If line items has leadToken we need to collect them
     * even if it's a contract type of warranty
     *
     * @param $lineItems
     * @return array
     */
    private function collectLeads($lineItems)
    {
        $leads = [];

        if (is_array($lineItems)) {
            foreach ($lineItems as $lineItem) {
                if (
                    !empty($lineItem['leadToken'])
                ) {
                    $leads[] = $lineItem['leadToken'];
                }
            }
        }
        return $leads;
    }

    /**
     * @param $lineItems
     * @return array
     */
    private function collectContracts($lineItems): array
    {
        $contracts = [];

        if (is_array($lineItems)) {
            foreach ($lineItems as $lineItem) {
                if (
                    isset($lineItem['type'])
                    && $lineItem['type'] === self::CONTRACT
                    && isset($lineItem['contractId'])
                ) {
                    $contracts[] = $lineItem['contractId'];
                }
            }
        }
        return $contracts;
    }

    /**
     * Sends fulfill status for order item
     *
     * Return status 'partial' if not all contracts
     * were created
     * When last contract created returns 'success'
     *
     * @param OrderInterface $orderMagento
     * @param OrderItemInterface $orderItem
     * @param int $qty
     * @return string
     * @throws LocalizedException
     */
    public function updateOrderItemStatus(
        OrderInterface     $orderMagento,
        OrderItemInterface $orderItem,
        int                $qty
    )
    {
        $storeId = $orderItem->getStoreId();

        $lineItemData = [
            'lineItemTransactionId' => AbstractLineItemBuilder::encodeTransactionId($orderItem)
        ];

        if (!$this->checkAndCreateOrder($orderMagento, $orderItem)) {
            throw new LocalizedException('Order doesn\'t exist on extend side and wasnt properly created.');
        }

        try {
            $fulfillRequest = $this->getFulfillRequest($storeId);
            if ($orderItem->getQtyOrdered() == $qty) {
                $updatedLineItems = $fulfillRequest
                    ->update($lineItemData, true);

            } else {
                $updatedLineItems = [];
                $contractQty = $qty;
                while ($contractQty > 0) {
                    $contractQty--;
                    $updatedLineItems[] = $fulfillRequest->update($lineItemData);
                }
            }

            $contractIds = $this->collectContracts($updatedLineItems);

            $contractCreateStatus = $this->saveContract($orderItem, $contractIds);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            throw new LocalizedException(new Phrase('Order API contract create error'), $e);
        }

        return empty($contractCreateStatus) ? '' : $contractCreateStatus;
    }

    /**
     * It's needed for backward compatibility with old behaviour
     * when order was created for separate order items.
     *
     * @param OrderInterface $magentoOrder
     * @param OrderItemInterface $orderItem
     * @return true
     * @throws LocalizedException
     */
    protected function checkAndCreateOrder($magentoOrder, $orderItem)
    {
        $storeId = $magentoOrder->getStoreId();
        $lineItemSearch = $this->getLineItemSearch($storeId);

        try {
            $extendOrder = $this->extendOrderRepository->get($magentoOrder->getEntityId());
        } catch (NoSuchEntityException $e) {
            $extendOrder = null;
            $this->logger->warning(
                'Order #' . $magentoOrder->getEntityId() . ' wasn\'t found on fulfilling item'
            );
        }

        if (!$extendOrder) {
            /**
             * If some lineitems found it means order exist on Extend Side
             * as lineItem has unique lineItemTransactionId=> "{orderId}:{itemId}"
             */
            $foundLineItems = $lineItemSearch->search([
                'lineItemTransactionId' => AbstractLineItemBuilder::encodeTransactionId($orderItem)
            ]);

            /**
             * if extend doesn't have order to create contract
             * then create it at this step
             */
            if (empty($foundLineItems)) {
                try {
                    $this->create($magentoOrder);
                } catch (LocalizedException $e) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Save Contract
     *
     * @param OrderItemInterface $orderItem
     * @param int $qty
     * @param array $contractIds
     * @return string
     */
    private function saveContract(OrderItemInterface $orderItem, array $contractIds): string
    {
        $contractIds = array_merge($this->getStoredContractIds($orderItem), $contractIds);
        $contractIdsJson = $this->jsonSerializer->serialize($contractIds);
        $orderItem->setContractId($contractIdsJson);
        $options = $orderItem->getProductOptions();
        $options['refund'] = false;
        $orderItem->setProductOptions($options);

        /**
         * fixes magento issue with storing entities in registry by entity_id
         * in some cases entity_id is null and ItemRepository::get($orderItemId)
         * returns wrong entity. So setting entity_id manually
         * so registry will have correct entity with contract id.
         *
         * without it contract id can be cleared on next save method call
         */

        $orderItem->setEntityId($orderItem->getId());
        $this->orderItemRepository->save($orderItem);

        return count($contractIds) == $orderItem->getQtyOrdered()
            ? ContractCreate::STATUS_SUCCESS
            : ContractCreate::STATUS_PARTIAL;
    }

    /**
     * Prepare Lead
     *
     * @param array $leadTokens
     * @return bool|string
     */
    private function prepareLead(array $leadTokens)
    {
        $leadTokens = array_unique($leadTokens);
        return $this->jsonSerializer->serialize($leadTokens);
    }

    /**
     * Get warranty contract IDs
     *
     * @param OrderItemInterface $orderItem
     * @return array
     */
    private function getStoredContractIds(OrderItemInterface $orderItem): array
    {
        try {
            $orderItemDb = $this->orderItemRepository->get($orderItem->getId());
            $contractIdsJson = $orderItemDb->getContractId();
            $contractIds = $contractIdsJson ? $this->jsonSerializer->unserialize($contractIdsJson) : [];
        } catch (Exception $exception) {
            $contractIds = [];
        }

        return $contractIds;
    }
}
