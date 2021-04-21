<?php


namespace Extend\Warranty\Model\Api;


use Extend\Warranty\Api\ConnectorInterface;
use Extend\Warranty\Api\Data\UrlBuilderInterface;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Zend_Http_Response;
use Extend\Warranty\Model\Keys;
use Extend\Warranty\Helper\Api\Data as Config;
use Magento\Framework\HTTP\ZendClient;


class Connector implements ConnectorInterface
{
    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var CurlFactory
     */
    protected $httpClient;


    /**
     * @var ZendClient
     */
    protected $client;

    /**
     * @var Keys
     */
    protected $keys;

    /**
     * @var UrlBuilderInterface
     */
    protected $urlBuilder;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $uri;

    public function __construct
    (
        ZendClient $client,
        Keys $keys,
        UrlBuilderInterface $urlBuilder,
        Config $config,
        Json $jsonSerializer,
        CurlFactory $httpClient
    )
    {
        $this->client = $client;
        $this->keys = $keys;
        $this->urlBuilder = $urlBuilder;
        $this->config = $config;

        $this->initClient();

        $this->jsonSerializer = $jsonSerializer;
        $this->httpClient = $httpClient;
    }

    public function testConnection(): bool
    {
        $response = $this->call("products");

        return $response->isSuccessful();

    }

    public function initClient(): void
    {
        $accessKeys = $this->keys->getKeys();

        $this->uri = '/stores/' . $accessKeys['store_id'];

        $this->client
            ->setHeaders([
                'Accept' => ' application/json; version=2020-08-01',
                'Content-Type' => ' application/json',
                'X-Extend-Access-Token' => $accessKeys['api_key']
            ]);

        $this->client->setConfig(
            [
                'timeout' => 20
            ]
        );
    }

    public function call(
        string $endpoint,
        string $method = \Zend_Http_Client::GET,
        array $data = null
    ): Zend_Http_Response
    {
        $this->uri = rtrim($this->uri);
        $endpoint = ltrim($endpoint);

        $_uri = "{$this->uri}/{$endpoint}";

        $this->client
            ->setUri($this->urlBuilder->setUri($_uri)->build())
            ->setMethod($method);

        if (
            isset($data) &&
            $method !== \Zend_Http_Client::GET
        ) {
            $this->client
                ->setRawData(
                    $this->jsonSerializer->serialize($data),
                    'application/json'
                );
        }
        return $this->client->request();
    }

    public function simpleCall(string $endpoint): string
    {
        $endpoint = ltrim($endpoint);
        return file_get_contents(
            $this->urlBuilder->setUri($endpoint)->build()
        );
    }
}