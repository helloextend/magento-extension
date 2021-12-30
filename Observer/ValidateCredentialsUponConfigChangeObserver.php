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

namespace Extend\Warranty\Observer;

use Extend\Warranty\Model\Api\Sync\Product\ProductsRequest as ApiProductModel;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ValidateCredentialsUponConfigChangeObserver
 */
class ValidateCredentialsUponConfigChangeObserver implements ObserverInterface
{
    /**
     * Context
     *
     * @var Context
     */
    private $context;

    /**
     * Message Manager Interface
     *
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * Array Manager
     *
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Api Product Model
     *
     * @var ApiProductModel
     */
    private $apiProductModel;

    /**
     * ValidateCredentialsUponConfigChangeObserver constructor
     *
     * @param Context $context
     * @param ManagerInterface $messageManager
     * @param ArrayManager $arrayManager
     * @param DataHelper $dataHelper
     * @param ApiProductModel $apiProductModel
     */
    public function __construct(
        Context $context,
        ManagerInterface $messageManager,
        ArrayManager $arrayManager,
        DataHelper $dataHelper,
        ApiProductModel $apiProductModel
    ) {
        $this->context = $context;
        $this->messageManager = $messageManager;
        $this->arrayManager = $arrayManager;
        $this->dataHelper = $dataHelper;
        $this->apiProductModel = $apiProductModel;
    }

    /**
     * Validate API credentials
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeId = 0;
        if ($event->getWebsite()) {
            $scopeType = ScopeInterface::SCOPE_WEBSITES;
            $scopeId = $event->getWebsite();
        } elseif ($event->getStore()) {
            $scopeType = ScopeInterface::SCOPE_STORES;
            $scopeId = $event->getStore();
        }

        if (!$this->needToValidate($scopeType, $scopeId)) {
            return;
        }

        $apiUrl = $this->dataHelper->getApiUrl($scopeType, $scopeId);
        $storeId = $this->dataHelper->getStoreId($scopeType, $scopeId);
        $apiKey = $this->dataHelper->getApiKey($scopeType, $scopeId);

        try {
            $this->apiProductModel->setConfig($apiUrl, $storeId, $apiKey);
            if ($this->apiProductModel->isConnectionSuccessful()) {
                $this->messageManager->addSuccessMessage(__('Extend is now enabled in your store.'));
                $this->messageManager->addSuccessMessage(__('Connection to Extend API is successful.'));
            } else {
                $this->messageManager->addErrorMessage(
                    __('Unable to connect to Extend API with the credentials provided.')
                );
            }
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage(
                __('Unable to connect to Extend API with the credentials provided.')
            );
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
    }

    /**
     * Check if need to validate API credentials
     *
     * @param string $scopeType
     * @param int|string|null $scopeId
     * @return bool
     */
    private function needToValidate(string $scopeType, $scopeId): bool
    {
        if (!$this->dataHelper->isExtendEnabled($scopeType, $scopeId)) {
            return false;
        }

        $request = $this->context->getRequest();
        $groups = $request->getParam('groups');

        if ($this->dataHelper->isExtendLive($scopeType, $scopeId)) {
            $storeIdInheritPath = 'authentication/fields/store_id/inherit';
            $apiKeyInheritPath = 'authentication/fields/api_key/inherit';
        } else {
            $storeIdInheritPath = 'authentication/fields/sandbox_store_id/inherit';
            $apiKeyInheritPath = 'authentication/fields/sandbox_api_key/inherit';
        }

        return !($this->arrayManager->exists($storeIdInheritPath, $groups)
            || $this->arrayManager->exists($apiKeyInheritPath, $groups));
    }
}
