<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api;

use Extend\Warranty\Api\Data\UrlBuilderInterface;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class UrlBuilder
 */
class UrlBuilder implements UrlBuilderInterface
{
    /**
     * App State
     *
     * @var AppState
     */
    private $appState;

    /**
     * Request Interface
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * Store Manager Interface
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * UrlBuilder constructor
     *
     * @param AppState $appState
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param DataHelper $dataHelper
     */
    public function __construct(
        AppState $appState,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        DataHelper $dataHelper
    ) {
        $this->appState = $appState;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Build url
     *
     * @param string $endpoint
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function build(string $endpoint): string
    {
        $scopeData = $this->getScopeData();
        $scope = $scopeData['scope'];
        $scopeId = $scopeData['scopeId'];

        $apiUrl = $this->dataHelper->getApiUrl($scope, $scopeId);
        $storeId = $this->dataHelper->getStoreId($scope, $scopeId);

        if (stripos($endpoint, 'offers') === 0 ||
            stripos($endpoint, 'orders') === 0 ||
            stripos($endpoint, 'refunds') === 0 ||
            stripos($endpoint, 'line-items') === 0) {

            return $apiUrl . $endpoint;
        } else {
            return $apiUrl . 'stores/' . $storeId . '/' . $endpoint;
        }
    }

    /**
     * Get API key
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getApiKey(): string
    {
        $scopeData = $this->getScopeData();
        $scope = $scopeData['scope'];
        $scopeId = $scopeData['scopeId'];

        return $this->dataHelper->getApiKey($scope, $scopeId);
    }

    /**
     * Get scope data
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getScopeData(): array
    {
        if ($this->appState->getAreaCode() === Area::AREA_FRONTEND) {
            $scope = ScopeInterface::SCOPE_STORES;
            $store = $this->storeManager->getStore();
            $scopeId = $store->getId();
        } else {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = 0;

            $params = $this->request->getParams();
            if (isset($params['scope'])) {
                $scope = $params['scope'];
                $scopeId = $params['scopeId'];
            } elseif (isset($params['store']) || isset($params['stores'])) {
                $scope = ScopeInterface::SCOPE_STORES;
                $scopeId = $params['store'] ?? $params['stores'];
            } elseif (isset($params['website']) || isset($params['websites'])) {
                $scope = ScopeInterface::SCOPE_WEBSITES;
                $scopeId = $params['website'] ?? $params['websites'];
            }
        }

        return [
            'scope' => $scope,
            'scopeId' => $scopeId,
        ];
    }

    public function getUuid4()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
