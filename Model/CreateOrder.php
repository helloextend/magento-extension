<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 *
 */

namespace Extend\Warranty\Model;

use Extend\Warranty\Model\ResourceModel\ContractCreate as ContractCreateResource;
use Extend\Warranty\Model\Orders as ExtendOrder;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Config\Source\CreateContractApi;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CreateContractApi
 */
class CreateOrder
{
    private const ORDER_ITEM_ID = 'order_item_id';
    private const ORDER_ID = 'order_id';
    private const QTY_ORDERED = 'qty';

    /**
     * ExtendOrder Model
     *
     * @var ExtendOrder
     */
    private $extendOrder;

    /**
     * Warranty Api DataHelper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Warranty Contract Create Factory
     *
     * @var ContractCreateFactory
     */
    private $contractCreateFactory;

    /**
     * Warranty Contract Create Resource
     *
     * @var ContractCreateResource
     */
    private $contractCreateResource;

    /**
     * Logger Model
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CreateContractApi constructor
     *
     * @param ExtendOrder $extendOrder
     * @param DataHelper $dataHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        ExtendOrder            $extendOrder,
        DataHelper             $dataHelper,
        ContractCreateFactory  $contractCreateFactory,
        ContractCreateResource $contractCreateResource,
        LoggerInterface        $logger
    )
    {
        $this->extendOrder = $extendOrder;
        $this->dataHelper = $dataHelper;
        $this->contractCreateFactory = $contractCreateFactory;
        $this->contractCreateResource = $contractCreateResource;
        $this->logger = $logger;
    }

    /**
     * @param OrderInterface $order
     * @param OrderItemInterface $warrantyItem
     * @param int $qty
     * @param int|string|null $storeId
     * @return void
     */
    public function createOrder(OrderInterface $order): void
    {


    }
}
