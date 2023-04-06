<?php
/**
 * @deprecated 1.3.0 Orders API should be used in all circumstances instead of the Contracts API.
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Sync\Lead;

use Extend\Warranty\Model\Api\Sync\AbstractRequest;
use InvalidArgumentException;

/**
 * Class LeadsRequest
 *
 * Warranty LeadsRequest
 */
class LeadsRequest extends AbstractRequest
{
    /**
     * Create a lead
     */
    public const CREATE_LEAD_ENDPOINT = 'leads/';

    /**
     * Response status codes
     */
    public const STATUS_CODE_SUCCESS = 201;

    /**
     * Create lead
     *
     * @param array $leadData
     * @return string
     */
    public function create(array $leadData): string
    {
        $leadToken = '';
        $response = $this->connector->call(
            $this->buildUrl(self::CREATE_LEAD_ENDPOINT),
            "POST",
            [self::ACCESS_TOKEN_HEADER => $this->apiKey],
            $leadData
        );
        if ($response->isSuccessful()) {
            $responseBody = $this->processResponse($response);
            $leadToken = $responseBody['leadToken'] ?? '';
            if ($leadToken) {
                $this->logger->info('Lead token is created successfully.');
            } else {
                $this->logger->error('Lead token creation is failed.');
            }
        }

        return $leadToken;
    }
}
