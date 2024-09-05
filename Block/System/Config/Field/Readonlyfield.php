<?php

namespace Extend\Warranty\Block\System\Config\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;

class ReadonlyField extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setData('readonly', 'readonly');
        return $element->getElementHtml();
    }
}
