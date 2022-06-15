<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Plugin\Logger;

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Logger;

/**
 * Class LoggerPlugin
 */
class LoggerPlugin
{
    /**
     * Warranty Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    public function __construct(DataHelper $data)
    {
        $this->dataHelper = $data;
    }

    /**
     * @param Logger $subject
     * @return false|void
     */
    public function beforeAddRecord(Logger $subject)
    {
        if (!$this->dataHelper->isExtendEnabled() || !$this->dataHelper->isLoggingEnabled()) {
            return false;
        }
    }
}
