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

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Backend\Block\Template\Context;
use Exception;

/**
 * Class Button
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
     * Data Helper
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
    public function __construct (
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
        $scopeData = $this->getScopeData();

        $lastSyncDate = $this->dataHelper->getLastProductSyncDate($scopeData['scope'], $scopeData['scopeId']);
        if (!empty($lastSyncDate)) {
            $lastSyncDate = $this->timezone->formatDate($lastSyncDate, 1, true);
        }

        return $lastSyncDate;
    }

    /**
     * Get scope data
     *
     * @return array
     */
    public function getScopeData(): array
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeId = 0;
        if (isset($params['store'])) {
            $scope = 'stores';
            $scopeId = $params['store'];
        } elseif (isset($params['website'])) {
            $scope = 'websites';
            $scopeId = $params['website'];
        }

        return [
            'scope' => $scope,
            'scopeId' => $scopeId,
        ];
    }
}
