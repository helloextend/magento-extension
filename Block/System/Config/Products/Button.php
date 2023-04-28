<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Block\System\Config\Products;

use Exception;
use Extend\Warranty\Block\System\Config\AbstractSyncButton;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Button
 *
 * Renders Button Field
 */
class Button extends AbstractSyncButton
{
    /**
     * Path to template file in theme
     *
     * @var string
     */
    protected $_template = "Extend_Warranty::system/config/products/button.phtml";

    /**
     * Get last sync date
     *
     * @return string
     * @throws Exception
     */
    public function getLastSync(): string
    {
        $storeIds = $this->getScopeStoreIds();
        $lastSyncDate = '';
        foreach($storeIds as $storeId){
            if($lastSyncDate){
                continue;
            }

            $lastSyncDate = $this->dataHelper->getLastProductSyncDate(ScopeInterface::SCOPE_STORE, $storeId);
        }

        if (!empty($lastSyncDate)) {
            $lastSyncDate = $this->timezone->formatDate($lastSyncDate, 1, true);
        }

        return $lastSyncDate;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSyncUrls()
    {
        $storeIds = $this->getScopeStoreIds();
        $syncUrls = [];
        foreach ($storeIds as $storeId) {
            $syncUrls[] = $this->getUrl('extend/products/sync', [ScopeInterface::SCOPE_STORE => $storeId]);
        }
        return $syncUrls;
    }
}
