<?php

namespace Extend\Warranty\Model\Api\Sync\Orders;

use Extend\Warranty\Model\Api\Response;
use Extend\Warranty\Model\Api\Sync\AbstractRequest;
use Extend\Warranty\Api\ConnectorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class OrdersRequest extends AbstractRequest
{
    /**
     * Create a warranty contract
     */
    public const CREATE_ORDER_ENDPOINT = 'orders';

    /**
     * Response status codes
     */
    public const STATUS_CODE_SUCCESS = 200;

    /**
     * Create an order
     *
     * @param array $orderData
     * @param string|null $type
     * @return array
     */
    public function create(array $orderData, ?string $type = 'contract'): array
    {
        $result = [];
        $url = $this->apiUrl . self::CREATE_ORDER_ENDPOINT;
        try {
            $response = $this->connector->call(
                $url,
                "POST",
                [
                    'Accept'                  => 'application/json; version=2022-02-01',
                    'Content-Type'            => 'application/json',
                    self::ACCESS_TOKEN_HEADER => $this->apiKey,
                    'X-Idempotency-Key'       => $this->getUuid4()
                ],
                $orderData
            );
            $responseBody = $this->processResponse($response);

            if ($type == \Extend\Warranty\Model\Orders::CONTRACT
                || $type == \Extend\Warranty\Model\Orders::LEAD_CONTRACT
            ) {
                $contractsIds = [];

                if(isset($responseBody['lineItems'])){
                    foreach ($responseBody['lineItems'] as $lineItem) {
                        if ($lineItem['status'] != 'unfulfilled') {
                            $contractsIds[] = $lineItem['contractId'];
                        }
                    }
                }

                $result = $contractsIds;
            } elseif ($type == \Extend\Warranty\Model\Orders::LEAD) {
                $leadsTokens = [];
                if(isset($responseBody['lineItems'])) {
                    foreach ($responseBody['lineItems'] as $lineItem) {
                        $leadsTokens[] = $lineItem['leadToken'];
                    }
                }

                $result = $leadsTokens;
            }

            $orderApiId = $responseBody['id'] ?? '';
            if ($orderApiId) {
                $this->logger->info('Order is created successfully. OrderApiID: ' . $orderApiId);
                if (!empty($contractsIds)) {
                    $this->logger->info('Contracts is created successfully. OrderApiID: ' . $orderApiId .
                        ' Contracts: ' . implode(', ', $contractsIds));
                }

                if (!empty($leadsTokens)) {
                    $this->logger->info('Leads is created successfully.
                     OrderApiID: ' . $orderApiId . ' Leads: ' . implode(', ', $leadsTokens));
                }
            } else {
                $this->logger->error('Order creation is failed.');
            }
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $result;
    }
}
