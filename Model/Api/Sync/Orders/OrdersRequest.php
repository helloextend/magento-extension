<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Sync\Orders;

use Extend\Warranty\Model\Api\Response\OrderResponse;
use Extend\Warranty\Model\Api\Sync\AbstractRequest;
use Extend\Warranty\Api\ConnectorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\ZendEscaper;
use Psr\Log\LoggerInterface;
use Extend\Warranty\Model\Api\Response\OrderResponseFactory;
use Zend_Http_Client;
use Zend_Http_Response;
use Zend_Http_Client_Exception;

class OrdersRequest extends AbstractRequest
{
    /**
     * Create a warranty contract
     */
    public const CREATE_ORDER_ENDPOINT = 'orders';

    /**
     * Response status codes
     */
    public const STATUS_CODE_SUCCESS = 200;

    protected $orderResponseFactory;

    public function __construct(
        ConnectorInterface   $connector,
        Json                 $jsonSerializer,
        ZendEscaper          $encoder,
        LoggerInterface      $logger,
        OrderResponseFactory $orderResponseFactory
    )
    {
        parent::__construct(
            $connector,
            $jsonSerializer,
            $encoder,
            $logger
        );
        $this->orderResponseFactory = $orderResponseFactory;
    }

    /**
     * Create an order
     *
     * @param array $orderData
     * @return OrderResponse
     * @throws Zend_Http_Client_Exception
     */
    public function create(array $orderData): OrderResponse
    {
        $url = $this->apiUrl . self::CREATE_ORDER_ENDPOINT;
        $orderResponse = $this->orderResponseFactory->create();
        try {
            $response = $this->connector->call(
                $url,
                Zend_Http_Client::POST,
                [
                    'Accept' => 'application/json; version=2022-02-01',
                    'Content-Type' => 'application/json',
                    self::ACCESS_TOKEN_HEADER => $this->apiKey,
                    'X-Idempotency-Key' => $this->getUuid4()
                ],
                $orderData
            );

            $responseBody = $this->processResponse($response);
            $orderResponse->setData($responseBody);

            if ($orderResponse->getId()) {
                $this->logger->info('Order is created successfully. OrderApiID: ' . $orderResponse->getId());
            } else {
                $this->logger->error('Order creation is failed.');
            }
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $orderResponse;
    }

    /**
     * Process response
     *
     * @param Zend_Http_Response $response
     * @return array
     */
    protected function processResponse(Zend_Http_Response $response): array
    {
        $responseBody = [];
        $responseBodyJson = $response->getBody();

        if ($responseBodyJson) {
            $responseBody = $this->jsonSerializer->unserialize($responseBodyJson);

            if (isset($responseBody['customer'])) {
                $depersonalizedBody = $responseBody;
                $depersonalizedBody['customer'] = [];
                $rawBody = $this->jsonSerializer->serialize($depersonalizedBody);
            } else {
                $rawBody = $response->getRawBody();
            }

            $this->logger->info('Response: ' . $response->getHeadersAsString() . PHP_EOL . $rawBody);
        } else {
            $this->logger->error('Response body is empty.');
        }

        return $responseBody;
    }
}
