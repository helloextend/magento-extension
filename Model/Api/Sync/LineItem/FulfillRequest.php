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

class FulfillRequest extends AbstractRequest
{
    const UPDATE_LINE_ITEM_ENDPOINT = 'line-items/fulfill';

    public function update(array $lineItemData, $fulfillAll = false)
    {
        $url = $this->apiUrl . self::UPDATE_LINE_ITEM_ENDPOINT;

        if ($fulfillAll === true) {
            $url .= '?fulfillAll=true';
        }

        try {
            $response = $this->connector->call(
                $url,
                Zend_Http_Client::POST,
                [
                    'Accept' => 'application/json; version=2022-02-01',
                    'Content-Type' => 'application/json',
                    self::ACCESS_TOKEN_HEADER => $this->apiKey,
                    'X-Idempotency-Key' => $this->getUuid4()
                ],
                $lineItemData
            );

            $lineItemResponse = $this->processResponse($response);

            if ($this->checkLineItemResponse($lineItemResponse)) {
                $this->logger->info('Line Item updated successfully.');
            } else {
                $this->logger->error('LineItem update is failed.');
            }
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $lineItemResponse;
    }

    private function checkLineItemResponse($lineItem)
    {
        /**
         * @TODO add logic here
         */
        return true;
    }
}