<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Controller\Adminhtml\Products\Sync;

use Extend\Warranty\Helper\Api\Data;
use Extend\Warranty\Model\ProductSyncFlag;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\FlagManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ResetFlag
 *
 * Reset the flag indicating that the products are being synchronized
 */
class ResetSyncDate extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Extend_Warranty::product_manual_sync';

    /**
     * Flag Manager Model
     *
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var Data
     */
    private $warrantyHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * ResetFlag constructor
     *
     * @param Context $context
     * @param Data $warrantyHelper
     * @param StoreManagerInterface $storeManager
     * @param FlagManager $flagManager
     */
    public function __construct(
        Context $context,
        Data $warrantyHelper,
        StoreManagerInterface $storeManager,
        FlagManager $flagManager
    ) {
        $this->flagManager = $flagManager;
        $this->warrantyHelper = $warrantyHelper;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Reset product sync flag
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $request = $this->getRequest();
        $this->flagManager->deleteFlag(ProductSyncFlag::FLAG_NAME);


        $website = $request->getParam('website');
        $store = $request->getParam('store');

        if ($website) {
            $websiteModel = $this->storeManager->getWebsite($website);
            $storeIds = $websiteModel->getStoreIds();
            $scopeType = ScopeInterface::SCOPE_WEBSITES;
            $scopeId = $website;
        } elseif ($store) {
            $scopeType = ScopeInterface::SCOPE_STORES;
            $scopeId = $store;
            $storeIds = [$store];
        } else {
            $stores = $this->storeManager->getStores();
            $storeIds = array_keys($stores);
            $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = Store::DEFAULT_STORE_ID;
        }

        foreach($storeIds as $storeId){
            $this->warrantyHelper->setLastProductSyncDate('', ScopeInterface::SCOPE_STORES, $storeId);
        }

        $this->warrantyHelper->setLastProductSyncDate('', $scopeType, $scopeId);
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData([
            'status' => true,
            'message' => __('Last sync Date has been reset.'),
        ]);

        return $resultJson;
    }
}
