<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */
namespace Extend\Warranty\CustomerData;

/**
 * Class Tracking
 *
 * Warranty Tracking
 */
class Tracking implements \Magento\Customer\CustomerData\SectionSourceInterface
{
    /**
     * Logger Model
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Warranty Tracking Helper
     *
     * @var \Extend\Warranty\Helper\Tracking
     */
    protected $_trackingHelper;

    /**
     * Tracking constructor.
     * @param \Extend\Warranty\Helper\Tracking $trackingHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Extend\Warranty\Helper\Tracking $trackingHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_trackingHelper = $trackingHelper;
        $this->_logger = $logger;
    }

    /**
     * Get Section Data
     *
     * @return array
     */
    public function getSectionData()
    {
        $data = [];
        if (!$this->_trackingHelper->isTrackingEnabled()) {
            return $data;
        }
        try {
            $trackingData = $this->_trackingHelper->getTrackingData();
            if (empty($trackingData)) {
                return $data;
            }
            return [
                'data' => $trackingData,
            ];
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }

        return $data;
    }
}
