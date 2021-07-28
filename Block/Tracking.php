<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */
namespace Extend\Warranty\Block;

/**
 * Class Tracking
 * @package Extend\Warranty\Block
 */
class Tracking extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Extend\Warranty\Helper\Tracking
     */
    private $_trackingHelper;

    /**
     * Tracking constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Extend\Warranty\Helper\Tracking $trackingHelper
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Extend\Warranty\Helper\Tracking $trackingHelper
    )
    {
        $this->_trackingHelper = $trackingHelper;

        parent::__construct(
            $context
        );
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_trackingHelper->isTrackingEnabled()) {
            return '';
        }

        return parent::_toHtml();
    }
}
