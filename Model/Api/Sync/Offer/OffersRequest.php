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

        $response = $this->connector->call(
            $url,
            "GET",
            [self::ACCESS_TOKEN_HEADER => $this->apiKey]
        );

        /** @var array $responseBody */
        $responseBody = $this->processResponse($response, false);

        return $responseBody;
    }
}
