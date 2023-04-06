<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Sync\Lead;

use Extend\Warranty\Model\Api\Sync\AbstractRequest;

/**
 * Class LeadInfoRequest
 *
 * Get Offer Information for a Lead
 */
class LeadInfoRequest extends AbstractRequest
{
    /**
     * Create a lead
     */
    public const GET_LEAD_INFO_ENDPOINT = 'leads/%s';

    /**
     * Response status codes
     */
    public const STATUS_CODE_SUCCESS = 200;

    /**
     * Get Offer Information for a Lead
     *
     * @param string $leadToken
     * @return int| null
     */
    public function create(string $leadToken): ?int
    {
        $url = $this->apiUrl . sprintf(self::GET_LEAD_INFO_ENDPOINT, $leadToken);
        $expirationDate = null;
        $response = $this->connector->call(
            $url,
            "GET",
            [self::ACCESS_TOKEN_HEADER => $this->apiKey]
        );

        if ($response->isSuccessful()) {
            $responseBody = $this->processResponse($response);
            $expirationDate = $responseBody['expirationDate'] ?? null;
            if (!$expirationDate) {
                $this->logger->error('Lead token expiration date is not set');
            }
        }

        return $expirationDate;
    }
}
