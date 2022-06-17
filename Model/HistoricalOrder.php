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

use Extend\Warranty\Api\Data\HistoricalOrderInterface;
use Extend\Warranty\Model\ResourceModel\HistoricalOrder as HistoricalOrderResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Class HistoricalOrder
 */
class HistoricalOrder extends AbstractModel implements HistoricalOrderInterface
{
    /**
     * Model construct that should be used for object initialization
     */
    public function _construct(): void
    {
        $this->_init(HistoricalOrderResource::class);
    }

    /**
     * @inheritDoc
     */
    public function setWasSent(bool $wasSent)
    {
        return $this->setData(self::WAS_SENT, (int)$wasSent);
    }

    /**
     * @inheritDoc
     */
    public function getWasSent()
    {
        return (bool)$this->getData(self::WAS_SENT);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEntityId(int $entityId)
    {
        return $this->setData(self::ENTITY_ID, (int)$entityId);
    }


}
