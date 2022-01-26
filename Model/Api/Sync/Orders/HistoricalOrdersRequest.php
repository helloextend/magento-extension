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

namespace Extend\Warranty\Model\Api\Sync\Orders;

use Extend\Warranty\Model\Api\Sync\AbstractRequest;
use Extend\Warranty\Api\ConnectorInterface;
use Extend\Warranty\Model\Api\Request\OrderBuilder as ExtendOrderBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Zend_Http_Client;
use Zend_Http_Response;

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

    private $orderApiBuilder;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $syncLogger;

    public function __construct(
        ConnectorInterface $connector,
        Json $jsonSerializer,
        ExtendOrderBuilder $orderApiBuilder,
        LoggerInterface $logger,
        LoggerInterface $syncLogger
    ) {
        parent::__construct($connector, $jsonSerializer, $logger);
        $this->syncLogger = $syncLogger;
        $this->orderApiBuilder = $orderApiBuilder;
    }

    /**
     * Send historical orders to Orders API
     *
     * @param array $ordersData
     * @param $currentBatch
     * @return void
     * @throws \Zend_Http_Client_Exception
     */
    public function create(array $ordersData, $currentBatch): void
    {
        $url = $this->apiUrl . self::CREATE_ORDER_ENDPOINT;
        $orders = $this->orderApiBuilder->preparePayloadBatch($ordersData);
        try {
            $response = $this->connector->call(
                $url,
                Zend_Http_Client::POST,
                [
                    'Accept'                  => 'application/json; version=2021-07-01',
                    'Content-Type'            => 'application/json',
                    self::ACCESS_TOKEN_HEADER => $this->apiKey,
                    'X-Idempotency-Key'       => $this->getUuid4()
                ],
                $orders
            );
            $responseBody = $this->processResponse($response);

            if ($response->getStatus() === self::STATUS_CODE_SUCCESS) {
                $this->logger->info(sprintf('Orders batch %s is synchronized successfully.', $currentBatch));
                $this->syncLogger->info('Synced ' . count($ordersData) . ' order(s) in batch ' . $currentBatch);

            } else {
                $this->logger->error(sprintf('Order batch %s synchronization is failed.', $currentBatch));
            }
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    protected function getUuid4()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
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
