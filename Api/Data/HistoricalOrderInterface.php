<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Api\Data;

/**
 * Historical Order Interface
 */
interface HistoricalOrderInterface
{
    /**
     * Historical orders table name
     */
    const DB_TABLE_NAME = 'extend_historical_orders';

    /**
     * Entity Id field
     */
    const ENTITY_ID = 'entity_id';

    /**
     * Was Sent field
     */
    const WAS_SENT = 'was_sent';

    /**
     * Set Entity Id
     *
     * @param int $entityId
     *
     * @return $this
     */
    public function setEntityId($entityId);

    /**
     * Set was sent
     *
     * @param bool $wasSent
     *
     * @return $this
     */
    public function setWasSent($wasSent);

    /**
     * Get Entity Id
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Get was sent
     *
     * @return bool
     */
    public function getWasSent();
}
