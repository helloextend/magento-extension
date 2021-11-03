<?php


namespace Extend\Warranty\Model;

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Api\Sync\Product\ProductsRequest;
use Psr\Log\LoggerInterface;

class SyncProcess
{
    /**
     * @var ProductsRequest
     */
    protected $productsRequest;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ProductsRequest $productsRequest,
        DataHelper $dataHelper,
        LoggerInterface $logger
    )
    {
        $this->productsRequest = $productsRequest;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
    }

    public function sync(array $storeProducts, $batch): void
    {
        $productsToSync = $this->processProducts($storeProducts);

        if (!empty($productsToSync)) {
            $this->productsRequest->create($productsToSync, $batch);
        } else {
            $this->logger->info('Nothing to sync in batch ' . $batch);
        }
    }

    private function processProducts(array $storeProducts): array
    {
        $lastGlobalSyncDate = $this->dataHelper->getLastProductSyncDate();

        if (empty($lastGlobalSyncDate)) {
            return $storeProducts;
        }

        $lastGlobalSyncDate = new \DateTime($lastGlobalSyncDate);

        foreach ($storeProducts as $key => $product) {
            $lastModifiedDate = new \DateTime($product->getUpdatedAt());

            //If product has not been updated
            if ($lastModifiedDate <= $lastGlobalSyncDate) {
                unset($storeProducts[$key]);
            }
        }

        return $storeProducts;
    }
}
