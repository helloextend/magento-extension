<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class GetAfterDate
 */
class GetAfterDate
{
    /**
     * Date conversion model
     *
     * @var DateTime
     */
    private $dateTime;

    /**
     * Scope Config
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * GetAfterDate constructor
     *
     * @param DateTime $dateTime
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        DateTime $dateTime,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get After Date Two Years
     *
     * @return string
     */
    public function getAfterDateTwoYears()
    {
        $beforeTwoYearsDate = "-2 years";
        $timeStamp = $this->dateTime->timestamp($beforeTwoYearsDate);

        return $this->dateTime->gmtDate('Y-m-d', $timeStamp);
    }
}
