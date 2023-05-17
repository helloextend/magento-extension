<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Sync\LineItem;

use Extend\Warranty\Model\Api\Sync\AbstractRequest;
use Magento\Framework\Exception\LocalizedException;
use Zend_Http_Client;

class LineItemSearch extends AbstractRequest
{
    const UPDATE_LINE_ITEM_ENDPOINT = 'line-items/search';

    public function search(array $lineItemData)
    {
        $url = $this->apiUrl . self::UPDATE_LINE_ITEM_ENDPOINT;

        $allowedSearchTerms = [
            'transactionId', // order-> incrementId
            'lineItemTransactionId'
        ];

        $url .= '?' . http_build_query($lineItemData);

        try {
            $response = $this->connector->call(
                $url,
                Zend_Http_Client::GET,
                [
                    'Accept' => 'application/json; version=2022-02-01',
                    'Content-Type' => 'application/json',
                    self::ACCESS_TOKEN_HEADER => $this->apiKey,
                    'X-Idempotency-Key' => $this->getUuid4()
                ]
            );

            $lineItemResponse = $this->processResponse($response);
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return empty($lineItemResponse['lineItems']) ? [] : $lineItemResponse['lineItems'];
    }

    private function checkLineItemResponse($lineItem)
    {
        return true;
    }
}