<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Controller\Adminhtml\Products;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Extend\Warranty\Api\SyncInterface as ProductSyncModel;
use Extend\Warranty\Model\ProductSyncFlag;
use Magento\Framework\FlagManager;
use Extend\Warranty\Model\Api\Sync\Product\ProductsRequest as ApiProductModel;
use Magento\Framework\Exception\InvalidArgumentException;

/**
 * Class Sync
 */
class Sync extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Extend_Warranty::product_manual_sync';

    /**
     * Status
     */
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_FAIL = 'FAIL';

    /**
     * Website ID filter
     */
    const WEBSITE_ID = 'website_id';

    /**
     * Flag Manager
     *
     * @var FlagManager
     */
    private $flagManager;

    /**
     * Date Time
     *
     * @var DateTime
     */
    private $dateTime;

    /**
     * Date
     *
     * @var Date
     */
    private $date;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Product Sync Model
     *
     * @var ProductSyncModel
     */
    private $productSyncModel;

    /**
     * Api Product Model
     *
     * @var ApiProductModel
     */
    private $apiProductModel;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $syncLogger;

    /**
     * Sync constructor
     *
     * @param Context $context
     * @param DataHelper $dataHelper
     * @param DateTime $dateTime
     * @param Date $date
     * @param FlagManager $flagManager
     * @param ProductSyncModel $productSyncModel
     * @param ApiProductModel $apiProductModel
     * @param LoggerInterface $logger
     * @param LoggerInterface $syncLogger
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        DateTime $dateTime,
        Date $date,
        FlagManager $flagManager,
        ProductSyncModel $productSyncModel,
        ApiProductModel $apiProductModel,
        LoggerInterface $logger,
        LoggerInterface $syncLogger
    ) {
        parent::__construct($context);
        $this->flagManager = $flagManager;
        $this->dateTime = $dateTime;
        $this->date = $date;
        $this->dataHelper = $dataHelper;
        $this->productSyncModel = $productSyncModel;
        $this->apiProductModel = $apiProductModel;
        $this->logger = $logger;
        $this->syncLogger = $syncLogger;
    }

    /**
     * Sync product batch
     *
     * @return ResultInterface
     * @throws InvalidArgumentException
     */
    public function execute(): ResultInterface
    {
        $request = $this->getRequest();
        $currentBatch = (int)$request->getParam('currentBatchesProcessed');

        if (!(bool)$this->flagManager->getFlagData(ProductSyncFlag::FLAG_NAME) || $currentBatch > 1) {
            if (!$this->flagManager->getFlagData(ProductSyncFlag::FLAG_NAME)) {
                $currentDate = $this->dateTime->formatDate($this->date->gmtTimestamp());
                $this->flagManager->saveFlag(ProductSyncFlag::FLAG_NAME, $currentDate);
            }

            $filters = [];
            $website = $request->getParam('website');
            $store = $request->getParam('store');
            if ($website) {
                $scopeType = ScopeInterface::SCOPE_WEBSITES;
                $scopeId = $website;
                $filters[self::WEBSITE_ID] = $website;
            } elseif ($store) {
                $scopeType = ScopeInterface::SCOPE_STORES;
                $scopeId = $store;
                $filters[Product::STORE_ID] = $store;
            } else {
                $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
                $scopeId = Store::DEFAULT_STORE_ID;
            }

            $apiUrl = $this->dataHelper->getApiUrl($scopeType, $scopeId);
            $apiStoreId = $this->dataHelper->getStoreId($scopeType, $scopeId);
            $apiKey = $this->dataHelper->getApiKey($scopeType, $scopeId);

            $this->apiProductModel->setConfig($apiUrl, $apiStoreId, $apiKey);

            $batchSize = $this->dataHelper->getProductsBatchSize($scopeType, $scopeId);
            $this->productSyncModel->setBatchSize($batchSize);

            $lastSyncDate = $this->dataHelper->getLastProductSyncDate($scopeType, $scopeId);
            if ($lastSyncDate) {
                $filters[ProductInterface::UPDATED_AT] = $lastSyncDate;
            }

            $products = $this->productSyncModel->getItems($currentBatch, $filters);
            $countOfBathes = $this->productSyncModel->getCountOfBatches();

            if (!empty($products)) {
                try {
                    $this->apiProductModel->create($products, $currentBatch);
                    $data['status'] = self::STATUS_SUCCESS;
                } catch (LocalizedException $exception) {
                    $message = sprintf('Error found in products batch %s. %s', $currentBatch, $exception->getMessage());
                    $this->syncLogger->error($message);
                    $data = [
                        'status'    => self::STATUS_FAIL,
                        'message'   => __($message),
                    ];
                }

                if ($currentBatch === $countOfBathes) {
                    $currentDate = $this->flagManager->getFlagData(ProductSyncFlag::FLAG_NAME);
                    $this->dataHelper->setLastProductSyncDate($currentDate, $scopeType, $scopeId);
                    $data['msg'] = $currentDate;
                    $this->flagManager->deleteFlag(ProductSyncFlag::FLAG_NAME);
                }
            } else {
                $currentDate = $this->flagManager->getFlagData(ProductSyncFlag::FLAG_NAME);
                $this->dataHelper->setLastProductSyncDate($currentDate, $scopeType, $scopeId);
                $data['msg'] = $currentDate;
                $this->flagManager->deleteFlag(ProductSyncFlag::FLAG_NAME);
            }

            $currentBatch++;
            $data['totalBatches'] = $countOfBathes;
            $data['currentBatchesProcessed'] = $currentBatch;
        } else {
            $data = [
                'status'    => self::STATUS_FAIL,
                'message'   => __('Product sync has already started by another process.'),
            ];
        }

        $jsonResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $jsonResult->setData($data);

        return $jsonResult;
    }
}
