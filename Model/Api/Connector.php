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
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface;
use Zend_Http_Client;
use Zend_Http_Response;
use Magento\Framework\HTTP\ZendClient;
use Zend_Http_Client_Exception;
use InvalidArgumentException;

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
     * @var ZendClient
     */
    private $httpClient;

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
     * Connector constructor
     *
     * @param ZendClient $httpClient
     * @param JsonSerializer $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        ZendClient $httpClient,
        JsonSerializer $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * Send request
     *
     * @param string $endpoint
     * @param string $method
     * @param array $headers
     * @param array $data
     * @return Zend_Http_Response
     * @throws Zend_Http_Client_Exception
     */
    public function call(
        string $endpoint,
        string $method = Zend_Http_Client::GET,
        array $headers = [],
        array $data = []
    ): Zend_Http_Response {
        $headers = array_merge(
            [
                'Accept'        => 'application/json; version=2021-04-01',
                'Content-Type'  => 'application/json',
            ],
            $headers
        );

        $this->httpClient->setUri($endpoint);
        $this->httpClient->setHeaders($headers);
        $this->httpClient->setMethod($method);
        $this->httpClient->setConfig(['timeout' => self::TIMEOUT]);

        if (!empty($data)) {
            try {
                $rawData = $this->jsonSerializer->serialize($data);
                $this->httpClient->setRawData($rawData);
            } catch (InvalidArgumentException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return $this->httpClient->request();
    }
}
