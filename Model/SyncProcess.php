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

namespace Extend\Warranty\Model;

use Magento\Framework\Stdlib\DateTime as DateTimeLib;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Api\Sync\Product\ProductsRequest;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use DateTime;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class SyncProcess
{
    /**
     * Products Request
     *
     * @var ProductsRequest
     */
    private $productsRequest;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Request Interface
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SyncProcess constructor
     *
     * @param ProductsRequest $productsRequest
     * @param DataHelper $dataHelper
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductsRequest $productsRequest,
        DataHelper $dataHelper,
        RequestInterface $request,
        LoggerInterface $logger
    ) {
        $this->productsRequest = $productsRequest;
        $this->dataHelper = $dataHelper;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * Product sync process
     *
     * @param array $storeProducts
     * @param int $batch
     * @throws NoSuchEntityException
     */
    public function sync(array $storeProducts, int $batch): void
    {
        $productsToSync = $this->processProducts($storeProducts);

        if (!empty($productsToSync)) {
            $this->productsRequest->create($productsToSync, $batch);
        } else {
            $this->logger->info('Nothing to sync in batch ' . $batch);
        }
    }

    /**
     * Process products
     *
     * @param array $storeProducts
     * @return array
     */
    private function processProducts(array $storeProducts): array
    {
        $params = $this->request->getParams();

        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeId = 0;
        if (isset($params['scope'])) {
            $scope = $params['scope'];
            $scopeId = $params['scopeId'];
        }

        $lastProductSyncDate = $this->dataHelper->getLastProductSyncDate($scope, $scopeId);
        if (!empty($lastProductSyncDate)) {
            $lastSyncDate = DateTime::createFromFormat(DateTimeLib::DATETIME_PHP_FORMAT, $lastProductSyncDate);
            foreach ($storeProducts as $key => $product) {
                $updatedAtDate = DateTime::createFromFormat(DateTimeLib::DATETIME_PHP_FORMAT, $product->getUpdatedAt());
                if ($updatedAtDate < $lastSyncDate) {
                    unset($storeProducts[$key]);
                }
            }
        }

        return $storeProducts;
    }
}
