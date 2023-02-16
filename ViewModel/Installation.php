<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\ViewModel;

use Extend\Warranty\Model\Config\Source\AuthMode;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Backend\Model\Auth\Session as AdminSession;
use InvalidArgumentException;

/**
 * Class Installation
 *
 * Warranty Installation ViewModel
 */
class Installation implements ArgumentInterface
{
    /**
     * Warranty Api DataHelper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Json Serializer Model
     *
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    private $adminSession;

    /**
     * Installation constructor
     *
     * @param DataHelper $dataHelper
     * @param JsonSerializer $jsonSerializer
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        DataHelper $dataHelper,
        JsonSerializer $jsonSerializer,
        StoreManagerInterface $storeManager,
        AdminSession $adminSession
    ) {
        $this->dataHelper = $dataHelper;
        $this->jsonSerializer = $jsonSerializer;
        $this->storeManager = $storeManager;
        $this->adminSession = $adminSession;
    }

    /**
     * Check if module enabled
     *
     * @return bool
     */
    public function isExtendEnabled(): bool
    {
        $result = false;
        if ($this->isAdmin()) {
            $stores = $this->storeManager->getStores();
            foreach ($stores as $store) {
                $result = $this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $store->getId());
                if ($result) {
                    break;
                }
            }
        } else {
            $storeId = $this->storeManager->getStore()->getId();
            $result = $this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId);
        }

        return $result;
    }

    /**
     * Get JSON config
     *
     * @return string
     */
    public function getJsonConfig(): string
    {
        $jsonConfig = '';

        $storeId = $this->dataHelper->getStoreId();
        if ($storeId) {
            $config = [
                'storeId' => $storeId,
                'environment' => $this->dataHelper->isExtendLive() ? AuthMode::LIVE : AuthMode::DEMO,
            ];

            if($this->isAdmin()){
                $config['region']='US';
            }

            try {
                $jsonConfig = $this->jsonSerializer->serialize($config);
            } catch (InvalidArgumentException $exception) {
                $jsonConfig = '';
            }
        }

        return $jsonConfig;
    }

    /**
     * Get JS mode
     *
     * @return string
     */
    public function getJsMode(): string
    {
        return "https://sdk.helloextend.com/extend-sdk-client/v1/extend-sdk-client.min.js";
    }

    /**
     * @return bool
     */
    private function isAdmin()
    {
        return (bool)$this->adminSession->getUser();
    }
}
