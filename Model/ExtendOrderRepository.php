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

use \Extend\Warranty\Model\ResourceModel\ExtendOrder as ExtendOrderResourceModel;
use Extend\Warranty\Model\ExtendOrderFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class ExtendOrderRepository
{
    /**
     * @var ExtendOrderResourceModel
     */
    protected $extendOrderResourceModel;

    /**
     * @var \Extend\Warranty\Model\ExtendOrderFactory
     */
    protected $extendOrderFactory;

    /**
     * @param ExtendOrderResourceModel $extendOrderResourceModel
     * @param \Extend\Warranty\Model\ExtendOrderFactory $extendOrderFactory
     */
    public function __construct(
        ExtendOrderResourceModel $extendOrderResourceModel,
        ExtendOrderFactory       $extendOrderFactory
    )
    {
        $this->extendOrderResourceModel = $extendOrderResourceModel;
        $this->extendOrderFactory = $extendOrderFactory;
    }

    /**
     * @param $orderId
     * @return ExtendOrder
     * @throws NoSuchEntityException
     */
    public function get($orderId)
    {
        /** @var ExtendOrder $extendOrder */
        $extendOrder = $this->extendOrderFactory->create();
        $this->extendOrderResourceModel->load($extendOrder, $orderId);

        if (!$extendOrder->getId()) {
            throw new NoSuchEntityException(__("Extend order for order %s doesn't exist."));
        }
        return $extendOrder;
    }

    /**
     * @param $extendOrder
     * @return $this
     * @throws CouldNotSaveException
     */
    public function save($extendOrder)
    {
        try {
            $this->extendOrderResourceModel->save($extendOrder);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Extend Order was unable to be saved. Please try again.'),
                $e
            );
        }
        return $this;
    }
}