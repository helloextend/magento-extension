<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Block\System\Config\Orders;

use Exception;
use Extend\Warranty\Block\System\Config\AbstractSyncButton;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Button
 */
class Button extends AbstractSyncButton
{
    /**
     * Path to template file in theme
     *
     * @var string
     */
    protected $_template = "Extend_Warranty::system/config/orders/button.phtml";

    /**
     * Get last sync date
     *
     * @return string
     * @throws Exception
     */
    public function getSyncPeriod(): string
    {
        $scopeData = $this->getScopeData();

        $period = $this->dataHelper->getHistoricalOrdersSyncPeriod($scopeData['scopeType'], $scopeData['scopeId']);

        return $period;
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
            $syncUrls[] = $this->getUrl('extend/orders/historicalorders', [ScopeInterface::SCOPE_STORE => $storeId]);
        }
        return $syncUrls;
    }
}
