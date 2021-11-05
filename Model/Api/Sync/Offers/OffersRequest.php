<?php

namespace Extend\Warranty\Model\Api\Sync\Offers;

use Extend\Warranty\Api\ConnectorInterface;
use Extend\Warranty\Api\Data\UrlBuilderInterface;
use Extend\Warranty\Helper\Api\Data as Config;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Setup\Exception;
use Psr\Log\LoggerInterface;

class OffersRequest
{
    const ENDPOINT_URI = '/offers';

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

    public function consult($productId)
    {
        return $this->consultRequest($productId);
    }

    private function consultRequest($productId)
    {
        try {
            $storeId = $this->config->getStoreId();
            $apiKey = $this->config->getApiKey();

            $endpoint = self::ENDPOINT_URI
                . "?storeId={$storeId}"
                . "&productId={$productId}";

            $response = $this->connector->simpleCall(
                $endpoint
            );
            return $this->processConsultResponse($response);

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
           return '';
        }
    }

    private function processConsultResponse($response)
    {
        return (!empty($response)) ? json_decode($response, true) : '';
    }
}
