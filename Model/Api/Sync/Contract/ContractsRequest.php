<?php

namespace Extend\Warranty\Model\Api\Sync\Contract;

use Extend\Warranty\Api\ConnectorInterface;
use Extend\Warranty\Model\Keys;
use Extend\Warranty\Api\Data\UrlBuilderInterface;
use Extend\Warranty\Helper\Api\Data as Config;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class ContractsRequest
{
    const ENDPOINT_URI = 'contracts';

    /**
     * @var Keys
     */
    protected $keys;

    /**
     * @var UrlBuilderInterface
     */
    protected $urlBuilder;

    /**
     * @var ConnectorInterface
     */
    protected $connector;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        Keys $keys,
        UrlBuilderInterface $urlBuilder,
        ConnectorInterface $connector,
        Config $config,
        Json $jsonSerializer,
        LoggerInterface $logger
    )
    {
        $this->keys = $keys;
        $this->urlBuilder = $urlBuilder;
        $this->connector = $connector;
        $this->config = $config;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    public function create($contract): string
    {
        return $this->createRequest($contract);
    }

    private function createRequest($contract): string
    {
        try {
            $response = $this->connector
                ->call(
                    self::ENDPOINT_URI,
                    \Zend_Http_Client::POST,
                    $contract
                );

            return $this->processCreateResponse($response);

        } catch (\Zend_Http_Client_Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            return '';
        }
    }

    private function processCreateResponse(\Zend_Http_Response $response): string
    {
        if ($response->isError()) {
            $res = $this->jsonSerializer->unserialize($response->getBody());
            $this->logger->error('Contract Request Fail', $res);

        } elseif ($response->getStatus() === 201 || $response->getStatus() === 202) {
            $res = $this->jsonSerializer->unserialize($response->getBody());
            $contractId = $res['id'];
            $this->logger->info(__('Contract #%1 request successful', $contractId));
            return $contractId;
        }

        return '';
    }


    public function refund($contractId): bool
    {
        return $this->refundRequest($contractId);
    }

    private function refundRequest($contractId): bool
    {
        try {
            $endpoint = self::ENDPOINT_URI . "/{$contractId}/refund";

            $response = $this->connector
                ->call(
                    $endpoint,
                    \Zend_Http_Client::POST
                );

            return $this->processRefundResponse($response);

        } catch (\Zend_Http_Client_Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            return false;
        }
    }

    private function processRefundResponse(\Zend_Http_Response $response): bool
    {
        if ($response->getStatus() === 201 || $response->getStatus() === 202) {
            $this->logger->info('Refund Request Success');
            return true;
        }

        $res = $this->jsonSerializer->unserialize($response->getBody());
        $this->logger->error('Refund Request Fail', $res);
        return false;

    }

}
