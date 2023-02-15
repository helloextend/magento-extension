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
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

/**
 * Class Button
 *
 * Renders Button Field
 */
class Button extends Field
{
    /**
     * Path to template file in theme
     *
     * @var string
     */
    protected $_template = "Extend_Warranty::system/config/products/button.phtml";

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * Warranty Api Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Button constructor
     *
     * @param Context $context
     * @param TimezoneInterface $timezone
     * @param DataHelper $dataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        TimezoneInterface $timezone,
        DataHelper $dataHelper,
        array $data = []
    ) {
        $this->timezone = $timezone;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope();
        $element->unsCanUseWebsiteValue();
        $element->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Render value
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _renderValue(AbstractElement $element): string
    {
        $html = '<td class="value">';
        $html .= $this->toHtml();
        $html .= '</td>';

        return $html;
    }

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
     * @return array|int[]|string[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getScopeStoreIds(){
        $request = $this->getRequest();
        $website = $request->getParam('website');
        $store = $request->getParam('store');

        if ($website) {
            $stores = $this->_storeManager->getWebsite($website)->getStores();
            $storeIds = array_keys($stores);
        } elseif ($store) {
            $storeIds = [$store];
        } else {
            $storeIds = array_keys($this->_storeManager->getStores());
        }

        return $storeIds;
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

    /**
     * Get scope data
     *
     * @return array
     */
    public function getScopeData(): array
    {
        $request = $this->getRequest();
        $website = $request->getParam('website');
        $store = $request->getParam('store');

        if ($website) {
            $scopeType = ScopeInterface::SCOPE_WEBSITE;
            $scopeId = $website;
        } elseif ($store) {
            $scopeType = ScopeInterface::SCOPE_STORE;
            $scopeId = $store;
        } else {
            $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = Store::DEFAULT_STORE_ID;
        }

        return [
            'scopeType' => $scopeType,
            'scopeId'   => $scopeId,
        ];
    }

    /**
     * Check if button enabled
     *
     * @return bool
     */
    public function isButtonEnabled(): bool
    {
        $scopeData = $this->getScopeData();

        return $this->dataHelper->getStoreId($scopeData['scopeType'], $scopeData['scopeId'])
                && $this->dataHelper->getApiKey($scopeData['scopeType'], $scopeData['scopeId']);
    }
}
