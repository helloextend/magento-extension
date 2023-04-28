<?php
/**
 * @author     Guidance Magento Team <magento@guidance.com>
 * @copyright  Copyright (c) 2023 Guidance Solutions (http://www.guidance.com)
 */

namespace Extend\Warranty\Model\Api\Request;

use Extend\Warranty\Helper\Data;
use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Model\WarrantyRelation;

class LineItemBuilder
{

    /**
     * @var WarrantyRelation
     */
    protected $warrantyRelation;

    protected $helper;

    public function __construct(
        WarrantyRelation $warrantyRelation,
        Data             $helper,
                         $item,
                         $lineItemBuilders = [])
    {
        $this->warrantyRelation = $warrantyRelation;
        $this->helper = $helper;
        $this->item = $item;
        $this->lineItemBuilders = $lineItemBuilders;
    }

    protected $item;

    public function getItem()
    {
        return $this->item;
    }

    protected $lineItemBuilders;

    /**
     * @param $item
     * @return void
     */
    public function preparePayload()
    {
        $payload = [];
        foreach ($this->lineItemBuilders as $lineItemBuilder) {
            $payload = array_merge($payload, $lineItemBuilder->preparePayload($this->getItem()));
        }
        return $payload;
    }
}