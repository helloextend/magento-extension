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
 *
 * Warranty Tracking Block
 */
class Tracking extends \Magento\Framework\View\Element\Template
{
    /**
     * Warranty Tracking Helper
     *
     * @var \Extend\Warranty\Helper\Tracking
     */
    private $_trackingHelper;

    /**
     * Checkout Session Model
     *
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * Tracking constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Extend\Warranty\Helper\Tracking $trackingHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Extend\Warranty\Helper\Tracking $trackingHelper,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_trackingHelper = $trackingHelper;
        $this->_checkoutSession = $checkoutSession;

        parent::__construct(
            $context
        );
    }

    /**
     * Get cart total
     *
     * @return string
     */
    public function getCartTotal()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_checkoutSession->getLastRealOrder();
        $grandTotal = (float)$order->getGrandTotal();

        return $grandTotal;
    }

    /**
     * Get html string
     *
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
