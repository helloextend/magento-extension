<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Sync\Product;

use Extend\Warranty\Api\ConnectorInterface;
use Extend\Warranty\Model\Api\Request\ProductDataBuilder as ProductPayloadBuilder;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Zend_Http_Client;
use Zend_Http_Response;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class ProductsRequest
 */
class ProductsRequest
{
    /**
     * Create / update a product
     */
    const CREATE_PRODUCT_ENDPOINT = 'products/';

    /**
     * Response status codes
     */
    const STATUS_CODE_SUCCESS = 201;

    /**
     * Connector Interface
     *
     * @var ConnectorInterface
     */
    private $connector;

    /**
     * Product Payload Builder
     *
     * @var ProductPayloadBuilder
     */
    private $productPayloadBuilder;

    /**
     * Json Serializer
     *
     * @var Json
     */
    private $jsonSerializer;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $syncLogger;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ProductsRequest constructor
     *
     * @param ConnectorInterface $connector
     * @param ProductPayloadBuilder $productPayloadBuilder
     * @param Json $jsonSerializer
     * @param LoggerInterface $syncLogger
     * @param LoggerInterface $logger
     */
    public function __construct (
        ConnectorInterface $connector,
        ProductPayloadBuilder $productPayloadBuilder,
        Json $jsonSerializer,
        LoggerInterface $syncLogger,
        LoggerInterface $logger
    ) {
        $this->connector = $connector;
        $this->productPayloadBuilder = $productPayloadBuilder;
        $this->jsonSerializer = $jsonSerializer;
        $this->syncLogger = $syncLogger;
        $this->logger = $logger;
    }

    /**
     * Create / update batch of products
     *
     * @param array $products
     * @param int $currentBatch
     * @throws NoSuchEntityException
     */
    public function create(array $products, int $currentBatch = 1)
    {
        $productData = [];
        foreach ($products as $product) {
            $productPayload = $this->productPayloadBuilder->build($product);
            if (!empty($productPayload)) {
                $productData[] = $productPayload;
            }
        }

        if (!empty($productData)) {
            try {
                $response = $this->connector->call(
                    self::CREATE_PRODUCT_ENDPOINT . '?batch=true',
                    Zend_Http_Client::POST,
                    $productData
                );
                $responseBody = $this->processResponse($response);

                if ($response->getStatus() === self::STATUS_CODE_SUCCESS) {
                    $this->logger->info(sprintf('Product batch %s is synchronized successfully.', $currentBatch));
                    $this->syncLogger->info('Synced ' . count($productData) . ' products in batch ' . $currentBatch);

                    foreach ($responseBody as $name => $section) {
                        $info = array_column($section, 'referenceId');
                        $this->syncLogger->info($name, $info);
                    }
                } else {
                    $this->logger->error(sprintf('Product batch %s synchronization is failed.', $currentBatch));
                }
            } catch (LocalizedException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
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
}
