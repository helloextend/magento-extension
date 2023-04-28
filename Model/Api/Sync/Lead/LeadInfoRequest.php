<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Sync\Lead;

use Extend\Warranty\Api\ConnectorInterface;
use Extend\Warranty\Model\Api\Response\LeadInfoResponse;
use Extend\Warranty\Model\Api\Response\LeadInfoResponseFactory;
use Extend\Warranty\Model\Api\Sync\AbstractRequest;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\ZendEscaper;
use Psr\Log\LoggerInterface;

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
     * @var LeadInfoResponseFactory
     */
    protected $leadResponseFactory;

    /**
     * @param ConnectorInterface $connector
     * @param JsonSerializer $jsonSerializer
     * @param ZendEscaper $encoder
     * @param LoggerInterface $logger
     * @param LeadInfoResponseFactory $leadInfoResponseFactory
     */
    public function __construct(
        ConnectorInterface      $connector,
        JsonSerializer          $jsonSerializer,
        ZendEscaper             $encoder,
        LoggerInterface         $logger,
        LeadInfoResponseFactory $leadInfoResponseFactory
    )
    {
        $this->leadResponseFactory = $leadInfoResponseFactory;
        parent::__construct($connector, $jsonSerializer, $encoder, $logger);
    }

    /**
     * Get Offer Information for a Lead
     *
     * @param string $leadToken
     * @return LeadInfoResponse
     */
    public function create(string $leadToken): LeadInfoResponse
    {
        $url = $this->apiUrl . sprintf(self::GET_LEAD_INFO_ENDPOINT, $leadToken);

        /** @var LeadInfoResponse $leadInfoResponse */
        $leadInfoResponse = $this->leadResponseFactory->create();

        $response = $this->connector->call(
            $url,
            "GET",
            [self::ACCESS_TOKEN_HEADER => $this->apiKey]
        );

        if ($response->isSuccessful()) {
            $responseBody = $this->processResponse($response);

            $leadInfoResponse->setExpirationDate($responseBody['expirationDate'] ?? null);
            $leadInfoResponse->setStatus($responseBody['status'] ?? '');

            if (!$leadInfoResponse->getExpirationDate()) {
                $this->logger->error('Lead token expiration date is not set');
            }
        }

        return $leadInfoResponse;
    }
}
