<?php
/**
 * @deprecated 1.3.0 Orders API should be used in all circumstances instead of the Contracts API.
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Sync\Contract;

use Extend\Warranty\Model\Api\Sync\AbstractRequest;

/**
 * Class ContractsRequest
 *
 * Warranty ContractsRequest
 */
class ContractsRequest extends AbstractRequest
{
    /**
     * Create a warranty contract
     */
    public const CREATE_CONTRACT_ENDPOINT = 'contracts/';

    /**
     * Cancel a warranty contract and request a refund
     */
    public const REFUND_CONTRACT_ENDPOINT = 'contracts/%s/refund';

    /**
     * Response status codes
     */
    public const STATUS_CODE_SUCCESS = 201;

    /**
     * Create a warranty contract
     *
     * @param array $contractData
     * @return string
     */
    public function create(array $contractData): string
    {
        $response = $this->connector->call(
            $this->buildUrl(self::CREATE_CONTRACT_ENDPOINT),
            "POST",
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

        $response = $this->connector->call(
            $this->buildUrl($endpoint),
            "POST",
            [self::ACCESS_TOKEN_HEADER => $this->apiKey]
        );

        /**
         * Processing response to put response to log
         */
        $this->processResponse($response);

        if ($response->getStatus() === self::STATUS_CODE_SUCCESS) {
            $isRefundRequested = true;
            $this->logger->info('Refund is requested successfully. ContractID: ' . $contractId);
        } else {
            $this->logger->error('Refund request is failed. ContractID: ' . $contractId);
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

        $response = $this->connector->call(
            $this->buildUrl($endpoint),
            "POST",
            [self::ACCESS_TOKEN_HEADER => $this->apiKey]
        );

        $responseBody = $this->processResponse($response, true);

        if ($response->getStatus() === self::STATUS_CODE_SUCCESS) {
            $this->logger->info('Refund is validated successfully. ContractID: ' . $contractId);
        } else {
            $this->logger->error('Refund validation is failed. ContractID: ' . $contractId);
        }

        return $responseBody;
    }
}
