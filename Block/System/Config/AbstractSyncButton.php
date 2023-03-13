<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Block\System\Config;

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

abstract class AbstractSyncButton extends Field
{
    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * Warranty Api Helper
     *
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * Button constructor
     *
     * @param Context $context
     * @param TimezoneInterface $timezone
     * @param DataHelper $dataHelper
     * @param array $data
     */
    public function __construct(
        Context           $context,
        TimezoneInterface $timezone,
        DataHelper        $dataHelper,
        array             $data = []
    )
    {
        $this->timezone = $timezone;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param AbstractElement $element
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
     * @return array|int[]|string[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getScopeStoreIds()
    {
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
            'scopeId' => $scopeId,
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