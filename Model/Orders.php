<?php

namespace Extend\Warranty\Model;

use Extend\Warranty\Model\Api\Request\OrderBuilder;
use Extend\Warranty\Model\Api\Sync\Orders\OrdersRequest;
use Extend\Warranty\Model\Api\Sync\Offers\OffersRequest;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface;

class Orders
{
    /**
     * @var OrdersRequest
     */
    protected $ordersRequest;

    /**
     * @var OrderBuilder
     */
    protected $orderBuilder;

    /**
     * @var OffersRequest
     */
    protected $offersRequest;

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
     * @param OffersRequest $offersRequest
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param JsonSerializer $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct
    (
        OrdersRequest $ordersRequest,
        OrderBuilder $orderBuilder,
        OffersRequest $offersRequest,
        OrderItemRepositoryInterface $orderItemRepository,
        JsonSerializer $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->ordersRequest = $ordersRequest;
        $this->orderBuilder = $orderBuilder;
        $this->offersRequest = $offersRequest;
        $this->orderItemRepository = $orderItemRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * @param $itemSku
     * @return array
     */
    public function getOffers($itemSku): array
    {
        $offers = $this->offersRequest->consult($itemSku);
        if (!empty($offers) && isset($offers['plans'])
            && is_array($offers['plans']) && count($offers['plans']) >= 1) {
            return $offers['plans'];
        }
        return [];
    }

    /**
     * @param $itemSku
     * @return bool
     */
    public function hasOffers($itemSku) :bool
    {
        $offerPlans = $this->getOffers($itemSku);

        if (
            !empty($offerPlans)
            && is_array($offerPlans)
            && count($offerPlans) >= 1
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param $orderMagento
     * @param $orderItem
     * @param $qtyInvoiced
     * @return string
     */
    public function createOrder($orderMagento, $orderItem, $qtyInvoiced) :string
    {
        $orderExtend = '';
        $contractIds = [];
        try {
            $orderData = $this->orderBuilder->preparePayload($orderMagento, $orderItem, $qtyInvoiced);
            $contractIds =  $this->ordersRequest->create($orderData);
            if (!empty($contractIds)) {
                $contractIdsJson = $this->jsonSerializer->serialize($contractIds);
                $orderItem->setContractId($contractIdsJson);
                $options = $orderItem->getProductOptions();
                $options['refund'] = false;
                $orderItem->setProductOptions($options);
                $this->orderItemRepository->save($orderItem);
                $orderExtend = count($contractIds) === $qtyInvoiced ? ContractCreate::STATUS_SUCCESS : ContractCreate::STATUS_PARTIAL;;
            }
        } catch(\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }

        return empty($orderExtend) ? '' : $orderExtend;
    }
}
