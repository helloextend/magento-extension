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
use Extend\Warranty\Model\Api\Response;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\ZendEscaper;
use Psr\Log\LoggerInterface;
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
        JsonSerializer     $jsonSerializer,
        ZendEscaper        $encoder,
        LoggerInterface    $logger
    )
    {
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
            $this->logger->warning('Credentials not Set.');
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
     * @param Response $response
     * @return array
     */
    public function processResponse(
        Response $response,
                 $logResponse = true
    ): array
    {
        $responseBody = [];
        $responseBodyJson = $response->getBody();

        if ($responseBodyJson) {
            try {
                $responseBody = $this->jsonSerializer->unserialize($responseBodyJson);

                if ($logResponse) {
                    $this->_logResponse(
                        $response->getRequestEndpoint(),
                        $responseBody,
                        $response->getHeadersAsString()
                    );
                }
            } catch (\InvalidArgumentException $e) {
                $this->logger->error(
                    'Response body failed to unserialize.'
                    . PHP_EOL
                    . 'Response:' . $responseBodyJson
                    . PHP_EOL
                    . 'Exception:' . $e->getMessage()
                );
            }
        } else {
            $this->logger->error('Response body is empty.');
        }

        return $responseBody;
    }


    /**
     * Logs the response with headers
     *
     * @param $requestEndpoint
     * @param $responseBody
     * @param $headers
     * @return void
     */
    protected function _logResponse($requestEndpoint, $responseBody, $headers = '')
    {

        /** If Code exists then Response contains some error and it should be depersonalized*/
        if (empty($responseBody['code'])) {
            $responseBody = $this->_depersonalizeData($responseBody);
        }
        try {
            $rawResponseBody = $this->jsonSerializer->serialize($responseBody);

            $this->logger->info(
                'Request URL:' . $requestEndpoint . PHP_EOL
                . 'Response Header: ' . PHP_EOL
                . (string)$headers . PHP_EOL
                . 'Response Body: ' . PHP_EOL
                . $rawResponseBody
            );
        } catch (\InvalidArgumentException $e) {
            $this->logger->error(
                'Failed process Response'
                . PHP_EOL
                . 'Exception:' . $e->getMessage()
            );
        }
    }

    /**
     * Removes personal data from response if exist
     *
     * @param $responseBody
     * @return array
     */
    protected function _depersonalizeData($responseBody): array
    {
        $depersonalizedBody = $responseBody;

        if (isset($depersonalizedBody['customer'])) {
            $depersonalizedBody['customer'] = [];
        }
        return $depersonalizedBody;
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
