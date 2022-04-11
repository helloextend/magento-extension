<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Sync\Lead;

use Extend\Warranty\Model\Api\Sync\AbstractRequest;
use Zend_Http_Client;
use Zend_Http_Client_Exception;
use InvalidArgumentException;

/**
 * Class LeadsRequest
 */
class LeadsRequest extends AbstractRequest
{
    /**
     * Create a lead
     */
    const CREATE_LEAD_ENDPOINT = 'leads/';

    /**
     * Create lead
     *
     * @param array $leadData
     * @return string
     */
    public function create(array $leadData): string
    {
        $leadToken = '';
        try {
            $response = $this->connector->call(
                $this->buildUrl(self::CREATE_LEAD_ENDPOINT),
                Zend_Http_Client::POST,
                [self::ACCESS_TOKEN_HEADER => $this->apiKey],
                $leadData
            );
            $responseBody = $this->processResponse($response);
            $leadToken = $responseBody['leadToken'] ?? '';
            if ($leadToken) {
                $this->logger->info('Lead token is created successfully.');
            } else {
                $this->logger->error('Lead token creation is failed.');
            }
        } catch (Zend_Http_Client_Exception|InvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $leadToken;
    }
}
