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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Zend_Http_Client;
use Zend_Http_Response;

/**
 * Class RefundRequest
 */
class RefundRequest extends AbstractRequest
{
    /**
     * Create a warranty contract
     */
    const REFUND_ENDPOINT = 'refunds';

    /**
     * Response status codes
     */
    const STATUS_CODE_SUCCESS = 201;

    /**
     * Cancel a warranty contract and request a refund
     *
     * @param string $contractId
     * @return bool
     */
    public function refund(string $contractId): bool
    {
        $url = $this->apiUrl . self::REFUND_ENDPOINT;
        $isRefundRequested = false;
        try {
            $response = $this->connector->call(
                $url,
                Zend_Http_Client::POST,
                [
                    'Accept'                  => 'application/json; version=2021-07-01',
                    'Content-Type'            => 'application/json',
                    self::ACCESS_TOKEN_HEADER => $this->apiKey
                ],
                ['contractId' => $contractId]
            );
            $this->processResponse($response);

            if ($response->getStatus() === self::STATUS_CODE_SUCCESS) {
                $isRefundRequested = true;
                $this->logger->info('Refund is validated successfully. ContractID: ' . $contractId);
            } else {
                $this->logger->error('Refund validation is failed. ContractID: ' . $contractId);
            }
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $isRefundRequested;
    }

    /**
     * Get preview of the cancellation, including the amount that would be refunded
     *
     * @param string $contractId
     * @return array
     */
    public function validateRefund(string $contractId): array
    {
        $url = $this->apiUrl . self::REFUND_ENDPOINT . '?contractId=' . $contractId;
        $responseBody = [];

        try {
            $response = $this->connector->call(
                $url,
                Zend_Http_Client::GET,
                [
                    'Accept'                  => 'application/json; version=2021-07-01',
                    'Content-Type'            => 'application/json',
                    self::ACCESS_TOKEN_HEADER => $this->apiKey
                ]
            );
            $responseBody = $this->processResponse($response);

            if ($response->getStatus() === self::STATUS_CODE_SUCCESS) {
                $this->logger->info('Refund is validated successfully. ContractID: ' . $contractId);
            } else {
                $this->logger->error('Refund validation is failed. ContractID: ' . $contractId);
            }
        } catch (Zend_Http_Client_Exception|InvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $responseBody;
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
