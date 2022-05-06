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
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\ZendEscaper;
use Psr\Log\LoggerInterface;
use Zend_Http_Response;
use Magento\Framework\Exception\LocalizedException;
use Exception;

/**
 * Class AbstractRequest
 */
abstract class AbstractRequest implements RequestInterface
{
    /**
     * 'X-Extend-Access-Token' header
     */
    public const ACCESS_TOKEN_HEADER = 'X-Extend-Access-Token';

    /**
     * Connector Interface
     *
     * @var ConnectorInterface
     */
    protected $connector;

    /**
     * Json Serializer Model
     *
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    /**
     * Url encoder
     *
     * @var ZendEscaper
     */
    private $encoder;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * API url param
     *
     * @var string
     */
    protected $apiUrl = '';

    /**
     * Store ID param
     *
     * @var string
     */
    protected $storeId = '';

    /**
     * API key param
     *
     * @var string
     */
    protected $apiKey = '';

    /**
     * AbstractRequest constructor
     *
     * @param ConnectorInterface $connector
     * @param JsonSerializer $jsonSerializer
     * @param ZendEscaper $encoder
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConnectorInterface $connector,
        JsonSerializer $jsonSerializer,
        ZendEscaper $encoder,
        LoggerInterface $logger
    ) {
        $this->connector = $connector;
        $this->jsonSerializer = $jsonSerializer;
        $this->encoder = $encoder;
        $this->logger = $logger;
    }

    /**
     * Set connector config
     *
     * @param string $apiUrl
     * @param string $storeId
     * @param string $apiKey
     * @throws LocalizedException
     */
    public function setConfig(string $apiUrl, string $storeId, string $apiKey)
    {
        if (empty($apiUrl) || empty($storeId) || empty($apiKey)) {
            throw new LocalizedException(__('Credentials not set.'));
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
     * @return string
     * @throws Exception
     */
    protected function getUuid4()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    /**
     * Encode url
     *
     * @param string $url
     *
     * @return string
     */
    protected function encode(string $url)
    {
        return $this->encoder->escapeUrl($url);
    }
}
