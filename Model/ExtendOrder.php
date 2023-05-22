<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model;

/**
 * @method \Extend\Warranty\Model\ResourceModel\ExtendOrder getResource()
 * @method \Extend\Warranty\Model\ResourceModel\ExtendOrder\Collection getCollection()
 */
class ExtendOrder extends \Magento\Framework\Model\AbstractModel
    implements \Extend\Warranty\Api\Data\ExtendOrderInterface,
    \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'extend_warranty_extendorder';
    protected $_cacheTag = 'extend_warranty_extendorder';
    protected $_eventPrefix = 'extend_warranty_extendorder';


    protected function _construct()
    {
        $this->_init('Extend\Warranty\Model\ResourceModel\ExtendOrder');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getOrderId()
    {
        return $this->getData('order_id');
    }

    public function getExtendOrderId()
    {
        return $this->getData('extend_order_id');
    }
}