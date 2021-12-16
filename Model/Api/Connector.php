<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

declare(strict_types=1);

namespace Extend\Warranty\Model\Api;

use Extend\Warranty\Api\ConnectorInterface;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface;
use Zend_Http_Client;
use Zend_Http_Response;
use Magento\Framework\HTTP\ZendClient;
use Zend_Http_Client_Exception;
use InvalidArgumentException;

/**
 * Class Connector
 */
class Connector implements ConnectorInterface
{
    /**
     * Timeout
     */
    const TIMEOUT = 20;

    /**
     * ZendClient
     *
     * @var ZendClient
     */
    private $httpClient;

    /**
     * Json Serializer
     *
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Url Builder
     *
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Connector constructor
     *
     * @param ZendClient $httpClient
     * @param JsonSerializer $jsonSerializer
     * @param DataHelper $dataHelper
     * @param UrlBuilder $urlBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        ZendClient $httpClient,
        JsonSerializer $jsonSerializer,
        DataHelper $dataHelper,
        UrlBuilder $urlBuilder,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->jsonSerializer = $jsonSerializer;
        $this->dataHelper = $dataHelper;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
    }

    /**
     * Send request
     *
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @return Zend_Http_Response
     * @throws Zend_Http_Client_Exception
     */
    public function call(
        string $endpoint,
        string $method = Zend_Http_Client::GET,
        array $data = []
    ): Zend_Http_Response {
        $apiUrl = $this->urlBuilder->build($endpoint);
        $headers = [
            'Accept'                => 'application/json; version=2021-07-01',
            'Content-Type'          => 'application/json',
            'X-Extend-Access-Token' => $this->urlBuilder->getApiKey(),
        ];

        if($this->dataHelper->isOrdersApiEnabled()) {
            $headers['X-Idempotency-Key'] = $this->urlBuilder->getUuid4();
        }

        $this->httpClient->setUri($apiUrl);
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

    /**
     * Test connection
     *
     * @return bool
     * @throws Zend_Http_Client_Exception
     */
    public function testConnection(): bool
    {
        $response = $this->call('products');

        return $response->isSuccessful();
    }
}
