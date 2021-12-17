<?php

namespace Extend\Warranty\Model\Api\Sync\Orders;


use Extend\Warranty\Api\ConnectorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Zend_Http_Client;
use Zend_Http_Response;

class OrdersRequest
{
    /**
     * Create a warranty contract
     */
    const CREATE_ORDER_ENDPOINT = 'orders';

    /**
     * Response status codes
     */
    const STATUS_CODE_SUCCESS = 200;

    /**
     * Connector Interface
     *
     * @var ConnectorInterface
     */
    private $connector;

    /**
     * Json Serializer
     *
     * @var Json
     */
    private $jsonSerializer;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ContractsRequest constructor
     *
     * @param ConnectorInterface $connector
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConnectorInterface $connector,
        Json $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->connector = $connector;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * Create an order
     *
     * @param array $orderData
     * @return array
     */
    public function create(array $orderData, $type = 'contract'): array
    {
        $result = [];
        try {
            $response = $this->connector->call(
                self::CREATE_ORDER_ENDPOINT,
                Zend_Http_Client::POST,
                $orderData
            );
            $responseBody = $this->processResponse($response);

            if ($type == \Extend\Warranty\Model\Orders::CONTRACT) {
                $contractsIds = [];
                foreach ($responseBody['lineItems'] as $lineItem) {
                    $contractsIds[] = $lineItem['contractId'];
                }

                $result = $contractsIds;
            } elseif ($type == \Extend\Warranty\Model\Orders::LEAD) {
                $leadsTokens = [];
                foreach ($responseBody['lineItems'] as $lineItem) {
                    $leadsTokens[] = $lineItem['leadToken'];
                }

                $result = $leadsTokens;
            }

            $orderApiId = $responseBody['id'] ?? '';
            if ($orderApiId) {
                $this->logger->info('Order is created successfully. OrderApiID: ' . $orderApiId);
            } else {
                $this->logger->error('Order creation is failed.');
            }
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $result;
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
