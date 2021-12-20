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

namespace Extend\Warranty\Model\Api\Sync\Offers;

use Extend\Warranty\Api\ConnectorInterface;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface;
use Zend_Http_Response;
use Exception;

/**
 * Class OffersRequest
 */
class OffersRequest
{
    /**
     * Get offer information
     */
    const GET_OFFER_INFO_ENDPOINT = 'offers?storeId=%s&productId=%s';

    /**
     * Connector Interface
     *
     * @var ConnectorInterface
     */
    private $connector;

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
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * OffersRequest constructor
     *
     * @param ConnectorInterface $connector
     * @param JsonSerializer $jsonSerializer
     * @param DataHelper $dataHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConnectorInterface $connector,
        JsonSerializer $jsonSerializer,
        DataHelper $dataHelper,
        LoggerInterface $logger
    ) {
        $this->connector = $connector;
        $this->jsonSerializer = $jsonSerializer;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
    }

    /**
     * Get offer information
     *
     * @param string $productId
     * @return array
     */
    public function consult(string $productId): array
    {
        $storeId = $this->dataHelper->getStoreId();
        $endpoint = sprintf(self::GET_OFFER_INFO_ENDPOINT, $storeId, $productId);

        try {
            $response = $this->connector->call($endpoint);
            $responseBody = $this->processResponse($response);
        } catch (Exception $exception) {
            $responseBody = [];
            $this->logger->error($exception->getMessage());
        }

        return $responseBody;
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
        }

        return $responseBody;
    }
}
