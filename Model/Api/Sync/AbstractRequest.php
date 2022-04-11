<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Sync;

use Extend\Warranty\Api\ConnectorInterface;
use Extend\Warranty\Api\RequestInterface;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface;
use Zend_Http_Response;

/**
 * Class AbstractRequest
 */
abstract class AbstractRequest implements RequestInterface
{
    /**
     * 'X-Extend-Access-Token' header
     */
    const ACCESS_TOKEN_HEADER = 'X-Extend-Access-Token';

    /**
     * Connector Interface
     *
     * @var ConnectorInterface
     */
    protected $connector;

    /**
     * Json Serializer
     *
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * API url
     *
     * @var string
     */
    protected $apiUrl = '';

    /**
     * Store ID
     *
     * @var string
     */
    protected $storeId = '';

    /**
     * API key
     *
     * @var string
     */
    protected $apiKey = '';

    /**
     * AbstractRequest constructor
     *
     * @param ConnectorInterface $connector
     * @param JsonSerializer $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConnectorInterface $connector,
        JsonSerializer $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->connector = $connector;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * Set connector config
     *
     * @param string $apiUrl
     * @param string $storeId
     * @param string $apiKey
     * @throws InvalidArgumentException
     */
    public function setConfig(string $apiUrl, string $storeId, string $apiKey)
    {
        if (empty($apiUrl) || empty($storeId) || empty($apiKey)) {
            throw new InvalidArgumentException(__('Credentials not set.'));
        }

        $this->apiUrl = $apiUrl;
        $this->storeId = $storeId;
        $this->apiKey = $apiKey;
    }

    /**
     * Build url
     *
     * @param string $endpoint
     * @return string
     */
    public function buildUrl(string $endpoint): string
    {
        return $this->apiUrl . 'stores/' . $this->storeId . '/' . $endpoint;
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
            $this->logger->info('Response: ' . $response->getHeadersAsString() . PHP_EOL . $response->getRawBody());
        } else {
            $this->logger->error('Response body is empty.');
        }

        return $responseBody;
    }

    /**
     * Generate Idempotent Requests key
     *
     * @return string
     */
    protected function getUuid4()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
