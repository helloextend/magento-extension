<?php

namespace Extend\Warranty\Model\Api\Sync\Leads;

use Extend\Warranty\Api\ConnectorInterface;
use Extend\Warranty\Api\Data\UrlBuilderInterface;
use Extend\Warranty\Helper\Api\Data as Config;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class LeadsRequest
{
    const ENDPOINT_URI = 'leads';

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
        UrlBuilderInterface $urlBuilder,
        ConnectorInterface $connector,
        Config $config,
        Json $jsonSerializer,
        LoggerInterface $logger
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->connector = $connector;
        $this->config = $config;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    public function create($lead): string
    {
        return $this->createRequest($lead);
    }

    private function createRequest($lead): string
    {
        try {
            $response = $this->connector
                ->call(
                    self::ENDPOINT_URI,
                    \Zend_Http_Client::POST,
                    $lead
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
            $this->logger->error('Lead Request Fail', $res);
        } elseif ($response->getStatus() === 201 || $response->getStatus() === 202) {
            $res = $this->jsonSerializer->unserialize($response->getBody());
            if (isset($res['leadToken']) && !empty($res['leadToken'])) {
                $leadToken = $res['leadToken'];
                $this->logger->info(__('Lead #%1 request successful', $leadToken));
                return $leadToken;
            }
        }

        return '';
    }
}
