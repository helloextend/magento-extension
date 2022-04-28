<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Sync\Contract;

use Extend\Warranty\Model\Api\Sync\AbstractRequest;
use Zend_Http_Client;
use Zend_Http_Response;
use Zend_Http_Client_Exception;
use InvalidArgumentException;

/**
 * Class ContractsRequest
 */
class ContractsRequest extends AbstractRequest
{
    /**
     * Create a warranty contract
     */
    const CREATE_CONTRACT_ENDPOINT = 'contracts/';

    /**
     * Cancel a warranty contract and request a refund
     */
    const REFUND_CONTRACT_ENDPOINT = 'contracts/%s/refund';

    /**
     * Response status codes
     */
    const STATUS_CODE_SUCCESS = 201;

    /**
     * Create a warranty contract
     *
     * @param array $contractData
     * @return string
     */
    public function create(array $contractData): string
    {
        $contractId = '';
        try {
            $response = $this->connector->call(
                $this->buildUrl(self::CREATE_CONTRACT_ENDPOINT),
                Zend_Http_Client::POST,
                [self::ACCESS_TOKEN_HEADER => $this->apiKey],
                $contractData
            );
            $responseBody = $this->processResponse($response);

            $contractId = $responseBody['id'] ?? '';
            if ($contractId) {
                $this->logger->info('Contract is created successfully. ContractID: ' . $contractId);
            } else {
                $this->logger->error('Contract creation is failed.');
            }
        } catch (Zend_Http_Client_Exception|InvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $contractId;
    }

    /**
     * Cancel a warranty contract and request a refund
     *
     * @param string $contractId
     * @return bool
     */
    public function refund(string $contractId): bool
    {
        $endpoint = sprintf(self::REFUND_CONTRACT_ENDPOINT, $contractId);
        $isRefundRequested = false;

        try {
            $response = $this->connector->call(
                $this->buildUrl($endpoint),
                Zend_Http_Client::POST,
                [self::ACCESS_TOKEN_HEADER => $this->apiKey]
            );
            $this->processResponse($response);

            if ($response->getStatus() === self::STATUS_CODE_SUCCESS) {
                $isRefundRequested = true;
                $this->logger->info('Refund is requested successfully. ContractID: ' . $contractId);
            } else {
                $this->logger->error('Refund request is failed. ContractID: ' . $contractId);
            }
        } catch (Zend_Http_Client_Exception|InvalidArgumentException $exception) {
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
        $endpoint = sprintf(self::REFUND_CONTRACT_ENDPOINT, $contractId) . '?commit=false';
        $responseBody = [];

        try {
            $response = $this->connector->call(
                $this->buildUrl($endpoint),
                Zend_Http_Client::POST,
                [self::ACCESS_TOKEN_HEADER => $this->apiKey]
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
