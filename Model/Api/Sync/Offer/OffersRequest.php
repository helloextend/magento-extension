<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Sync\Offer;

use Extend\Warranty\Model\Api\Sync\AbstractRequest;
use Zend_Http_Response;
use Zend_Http_Client;
use Zend_Http_Client_Exception;
use InvalidArgumentException;

/**
 * Class OffersRequest
 *
 * Warranty OffersRequest
 */
class OffersRequest extends AbstractRequest
{
    /**
     * Get offer information
     */
    public const GET_OFFER_INFO_ENDPOINT = 'offers?storeId=%s&productId=%s';

    /**
     * Get offer information
     *
     * @param string $productSku
     * @return array
     */
    public function getOfferInformation(string $productSku): array
    {
        $url = $this->apiUrl . sprintf(self::GET_OFFER_INFO_ENDPOINT, $this->storeId, $this->encode($productSku));

        try {
            $response = $this->connector->call(
                $url,
                Zend_Http_Client::GET,
                [self::ACCESS_TOKEN_HEADER => $this->apiKey]
            );
            $responseBody = $this->processResponse($response);
        } catch (Zend_Http_Client_Exception|InvalidArgumentException $exception) {
            $responseBody = [];
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
        }

        return $responseBody;
    }
}
