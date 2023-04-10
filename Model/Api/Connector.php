<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api;

use Extend\Warranty\Api\ConnectorInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl as ClientInterface;
use Magento\Framework\HTTP\Client\CurlFactory as ClientInterfaceFactory;
use InvalidArgumentException;
use Extend\Warranty\Model\Api\ResponseFactory;

/**
 * Class Connector
 *
 * Warranty Connector
 */
class Connector implements ConnectorInterface
{
    /**
     * Timeout
     */
    public const TIMEOUT = 20;

    /**
     * Zend Http Client
     *
     * @var ClientInterfaceFactory
     */
    private $httpClientFactory;

    /**
     * Json Serializer Model
     *
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * Logger Model
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * Connector constructor
     *
     * @param ClientInterfaceFactory $httpClient
     * @param JsonSerializer $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        ClientInterfaceFactory $httpClient,
        JsonSerializer         $jsonSerializer,
        LoggerInterface        $logger,
        ResponseFactory        $responseFactory
    )
    {
        $this->httpClientFactory = $httpClient;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
    }


    /**
     * @param string $endpoint
     * @param array $headers
     * @return ClientInterface
     */
    public function callGet(
        string $endpoint,
        array  $headers
    )
    {
        $headers = array_merge(
            [
                'Accept' => 'application/json; version=2021-04-01',
                'Content-Type' => 'application/json',
            ],
            $headers
        );

        $client = $this->httpClientFactory->create();
        $client->setHeaders($headers);
        $client->setTimeout(self::TIMEOUT);
        $client->get($endpoint);

        return $client;
    }

    /**
     * @param string $endpoint
     * @param array $headers
     * @param array $data
     * @return ClientInterface
     */
    public function callPost(
        string $endpoint,
        array  $headers,
        array  $data = []
    )
    {

        $headers = array_merge(
            [
                'Accept' => 'application/json; version=2021-04-01',
                'Content-Type' => 'application/json',
            ],
            $headers
        );

        /** @var Curl $client */
        $client = $this->httpClientFactory->create();
        $client->setHeaders($headers);

        $rawData = '{}';
        if (!empty($data)) {
            try {
                $rawData = $this->jsonSerializer->serialize($data);
            } catch (InvalidArgumentException $exception) {
                $this->logger->error(
                    'Caught Exception on serializing data.' . PHP_EOL
                    . 'Endpoint:' . $endpoint
                    . 'Exception:' . $exception->getMessage()
                );
            }
        }

        $client->post($endpoint, $rawData);
        return $client;
    }

    /**
     * Send request
     *
     * @param string $endpoint
     * @param string $method
     * @param array $headers
     * @param array $data
     * @return Response
     */
    public function call(
        string $endpoint,
        string $method = "GET",
        array  $headers = [],
        array  $data = []
    ): Response
    {
        switch (strtoupper($method)) {
            case "GET":
                /** @var ClientInterface $client */
                $client = $this->callGet($endpoint, $headers);
                break;
            case "POST":
                /** @var ClientInterface $client */
                $client = $this->callPost($endpoint, $headers, $data);
                break;
        }

        /** @var Response $response */
        $response = $this->responseFactory->create();

        $response->setBody($client->getBody())
            ->setRequestEndpoint($endpoint)
            ->setHeaders($client->getHeaders())
            ->setStatusCode($client->getStatus());
        return $response;
    }
}
