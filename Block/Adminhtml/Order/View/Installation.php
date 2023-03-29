<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */
namespace Extend\Warranty\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Installation extends Template
{

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        Template\Context            $context,
        \Magento\Framework\Registry $registry,
        array                       $data = []
    )
    {
        $this->_coreRegistry = $registry;
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * @return null|int
     */
    public function getCurrentStore()
    {
        $storeId = null;
        if ($order = $this->_coreRegistry->registry('sales_order')) {
            $storeId = $order->getStoreId();
        }
        return $storeId;
    }
}