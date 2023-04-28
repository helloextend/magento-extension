<?php
/**
 * @author     Guidance Magento Team <magento@guidance.com>
 * @copyright  Copyright (c) 2023 Guidance Solutions (http://www.guidance.com)
 */

namespace Extend\Warranty\Model\Api\Request\LineItem;

use Extend\Warranty\Model\Api\Request\LineItemBuilder;

class NonWarrantableLineItemBuilder
{
    public function preparePayload($item)
    {
        return [];
    }
}