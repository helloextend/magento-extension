<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api\Response;

class LeadInfoResponse
{
    /** @var int|null */
    protected $expirationDate;

    /**
     * @var string
     */
    protected $status;

    const STATUS_LIVE = 'live';

    const STATUS_CONSUMED = 'consumed';

    /**
     * @param int|null $date
     * @return $this
     */
    public function setExpirationDate($date)
    {
        $this->expirationDate = $date ? $date / 1000 : null;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return (string)$this->status;
    }
}