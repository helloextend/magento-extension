<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Block\Sales\Order\Items;

use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Model\WarrantyRelation;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderItemInterface;

class Renderer extends \Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer
{

    /**
     * @var WarrantyRelation
     */
    protected $warrantyRelation;

    /**
     * @var OrderItemInterface | null
     */
    protected $associatedItem;

    /**
     * @param Context $context
     * @param StringUtils $string
     * @param OptionFactory $productOptionFactory
     * @param WarrantyRelation $warrantyRelation
     * @param array $data
     */
    public function __construct(
        Context          $context,
        StringUtils      $string,
        OptionFactory    $productOptionFactory,
        WarrantyRelation $warrantyRelation,
        array            $data = [])
    {
        parent::__construct($context, $string, $productOptionFactory, $data);
        $this->warrantyRelation = $warrantyRelation;
    }

    /**
     * Return associated warrantable order item to current warranty item
     *
     * @return \Magento\Sales\Model\Order\Item
     */
    public function getAssociatedItem()
    {
        if ($this->associatedItem === null) {
            $warrantyItem = $this->getItem();

            foreach ($this->getOrder()->getAllItems() as $orderItem) {
                if ($orderItem->getProductType() == Type::TYPE_CODE) {
                    continue;
                }
                if ($this->warrantyRelation->isWarrantyRelatedToOrderItem($warrantyItem, $orderItem)) {
                    $this->associatedItem = $orderItem;
                    break;
                }
            }
        }

        return $this->associatedItem;
    }
}