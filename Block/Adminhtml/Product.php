<?php

namespace Extend\Warranty\Block\Adminhtml;

use Magento\Catalog\Block\Adminhtml\Product as SuperBlock;
use Extend\Warranty\Helper\Data;

class Product extends SuperBlock
{
    protected function _getAddProductButtonOptions()
    {
        $splitButtonOptions = [];
        $types = $this->_typeFactory->create()->getTypes();
        uasort(
            $types,
            function ($elementOne, $elementTwo) {
                return ($elementOne['sort_order'] < $elementTwo['sort_order']) ? -1 : 1;
            }
        );

        foreach ($types as $typeId => $type) {
            if (!in_array($typeId, Data::NOT_ALLOWED_TYPES)) {
                $splitButtonOptions[$typeId] = [
                    'label' => __($type['label']),
                    'onclick' => "setLocation('" . $this->_getProductCreateUrl($typeId) . "')",
                    'default' => \Magento\Catalog\Model\Product\Type::DEFAULT_TYPE == $typeId,
                ];
            }
        }

        return $splitButtonOptions;
    }
}
