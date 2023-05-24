<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model;

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Api\Sync\Lead\LeadInfoRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

class LeadInfo
{

    /**
     * Data Helper Model
     *
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var LeadInfoRequest
     */
    private $leadInfoRequest;

    protected $leads = [];

    public function __construct(
        DataHelper      $dataHelper,
        LeadInfoRequest $leadInfoRequest
    )
    {
        $this->leadInfoRequest = $leadInfoRequest;
        $this->dataHelper = $dataHelper;
    }

    public function getLeadInfo($leadToken, $storeId = null)
    {
        if (!isset($this->leads[$leadToken])) {
            $this->leads[$leadToken] = $this->getLeadInforequest($storeId)->getLead($leadToken);
        }

        return $this->leads[$leadToken];
    }

    /**
     * @param $storeId
     * @return mixed
     */
    protected function getLeadInforequest($storeId)
    {
        $apiUrl = $this->dataHelper->getApiUrl(ScopeInterface::SCOPE_STORES, $storeId);
        $apiStoreId = $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId);
        $apiKey = $this->dataHelper->getApiKey(ScopeInterface::SCOPE_STORES, $storeId);

        try {
            $this->leadInfoRequest->setConfig($apiUrl, $apiStoreId, $apiKey);
        } catch (LocalizedException $e) {

        }

        return $this->leadInfoRequest;
    }

}