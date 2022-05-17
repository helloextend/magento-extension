<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Button
 *
 * Button Config Block
 */
class Button extends Field
{
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
     * Return element html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $originalData = $element->getOriginalData();

        return sprintf(
            "<a href='%s' class='action action-extend-external' target='_blank'>%s</a>",
            $originalData['button_url'],
            $originalData['button_label']
        );
    }
}
