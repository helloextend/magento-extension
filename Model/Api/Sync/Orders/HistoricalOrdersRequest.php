<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Sync\Orders;

use Extend\Warranty\Api\ConnectorInterface;
use Extend\Warranty\Model\Api\Sync\AbstractRequest;
use Extend\Warranty\Model\Api\Request\OrderBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\ZendEscaper;
use Psr\Log\LoggerInterface;

class HistoricalOrdersRequest extends AbstractRequest
{
    /**
     * Create a warranty contract
     */
    const CREATE_ORDER_ENDPOINT = 'orders/batch';

    /**
     * Response status codes
     */
    const STATUS_CODE_SUCCESS = 201;

    /**
     * @var OrderBuilder
     */
    protected $orderBuilder;

    /**
     * @param ConnectorInterface $connector
     * @param JsonSerializer $jsonSerializer
     * @param ZendEscaper $encoder
     * @param LoggerInterface $logger
     * @param OrderBuilder $orderBuilder
     */
    public function __construct(
        ConnectorInterface $connector,
        JsonSerializer $jsonSerializer,
        ZendEscaper $encoder,
        LoggerInterface $logger,
        OrderBuilder $orderBuilder

    )
    {
        parent::__construct($connector, $jsonSerializer, $encoder, $logger);
        $this->orderBuilder = $orderBuilder;
    }

    /**
     * Send historical orders to Orders API
     *
     * @param array $orders
     * @param int $currentBatch
     * @return bool
     */
    public function create(array $orders, int $currentBatch = 1): bool
    {
        $url = $this->apiUrl . self::CREATE_ORDER_ENDPOINT;
        $url .= "?historical=true";
        $historicalOrders = [];

        foreach ($orders as $order) {
            $historicalOrder = $this->orderBuilder->prepareHistoricalOrdersPayLoad($order);

            if (!empty($historicalOrder)) {
                $historicalOrders[] = $historicalOrder;
            }
        }

        if (!empty($historicalOrders)) {
            try {
                $response = $this->connector->call(
                    $url,
                    "POST",
                    [
                        'Accept' => 'application/json; version=2021-07-01',
                        'Content-Type' => 'application/json',
                        self::ACCESS_TOKEN_HEADER => $this->apiKey,
                        'X-Idempotency-Key' => $this->getUuid4()
                    ],
                    $historicalOrders
                );

                /** Processing response to put it to log */
                $this->processResponse($response);

                if ($response->getStatus() === self::STATUS_CODE_SUCCESS) {
                    $this->logger->info(sprintf('Orders batch %s is synchronized successfully.', $currentBatch));
                    return true;
                } else {
                    $this->logger->error(sprintf('Order batch %s synchronization is failed.', $currentBatch));
                }
            } catch (LocalizedException $exception) {
                $this->logger->error(sprintf('Order batch %s synchronization is failed. Error message: %s', $currentBatch, $exception->getMessage()));
            }
        }

        return false;
    }

    /**
     * Historical orders request sends orders
     * in a batch, result is batch as well
     * so it should be depersonalized iterrationaly
     *
     *
     * @param $responseBody
     * @return array
     */
    protected function _depersonalizeData($responseBody): array
    {
        $depersonalizedData = [];
        if (is_array($depersonalizedData)) {
            foreach ($responseBody as $responseDataRow) {
                $depersonalizedData[] = parent::_depersonalizeData($responseDataRow);
            }
        }
        return $depersonalizedData;
    }
}
