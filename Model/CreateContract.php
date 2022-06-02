<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model;

use Extend\Warranty\Model\Product\Type as WarrantyType;
use Extend\Warranty\Model\WarrantyContract;
use Extend\Warranty\Model\ContractCreateFactory;
use Extend\Warranty\Model\ResourceModel\ContractCreate as ContractCreateResource;
use Extend\Warranty\Model\Orders as ExtendOrder;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Config\Source\CreateContractApi;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Class CreateContractApi
 */
class CreateContract
{
    private const ORDER_ITEM_ID = 'order_item_id';
    /**
     * Warranty Contract Model
     *
     * @var WarrantyContract
     */
    private $warrantyContract;

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
     * @param WarrantyContract $warrantyContract
     * @param ExtendOrder $extendOrder
     * @param DataHelper $dataHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        WarrantyContract $warrantyContract,
        ExtendOrder $extendOrder,
        DataHelper $dataHelper,
        ContractCreateFactory $contractCreateFactory,
        ContractCreateResource $contractCreateResource,
        LoggerInterface $logger
    ) {
        $this->warrantyContract = $warrantyContract;
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
    public function createContract(OrderInterface $order, OrderItemInterface $warrantyItem, int $qty, int|string|null $storeId) :void
    {
        if ($this->dataHelper->getContractCreateApi(ScopeInterface::SCOPE_STORES, $storeId) ==
            CreateContractApi::CONTACTS_API
        ) {
            try {
                if ($warrantyItem->getLeadToken() != null &&
                    implode(", ", json_decode($warrantyItem->getLeadToken(), true)) != null
                ) {
                    $this->warrantyContract->create(
                        $order,
                        $warrantyItem,
                        $qty,
                        \Extend\Warranty\Model\WarrantyContract::LEAD_CONTRACT
                    );
                } else {
                    $this->warrantyContract->create(
                        $order,
                        $warrantyItem,
                        $qty,
                        \Extend\Warranty\Model\WarrantyContract::CONTRACT
                    );
                }
            } catch (LocalizedException $exception) {
                $this->logger->error(
                    'Error during warranty contract creation. ' . $exception->getMessage()
                );
            }
        } elseif ($this->dataHelper->getContractCreateApi(ScopeInterface::SCOPE_STORES, $storeId) ==
            CreateContractApi::ORDERS_API
        ) {
            try {
                if ($warrantyItem->getLeadToken() != null &&
                    implode(", ", json_decode($warrantyItem->getLeadToken(), true)) != null
                ) {
                    $this->extendOrder->createOrder(
                        $order,
                        $warrantyItem,
                        $qty,
                        \Extend\Warranty\Model\Orders::LEAD_CONTRACT
                    );
                } else {
                    $this->extendOrder->createOrder(
                        $order,
                        $warrantyItem,
                        $qty,
                        \Extend\Warranty\Model\Orders::CONTRACT
                    );
                }
            } catch (LocalizedException $exception) {
                $this->logger->error(
                    'Error during warranty order api contract creation. ' . $exception->getMessage()
                );
            }
        }
    }

    /**
     * @param OrderItemInterface $warrantyItem
     * @param int $qtyOrdered
     * @return void
     * @throws Exception
     */
    public function addContactToQueue(OrderItemInterface $warrantyItem, int $qtyOrdered): void
    {
        try {
            $contractCreate = $this->contractCreateFactory->create();
            $contractCreate->setData([
                self::ORDER_ITEM_ID => $warrantyItem->getId(),
                OrderItemInterface::QTY_ORDERED => $qtyOrdered,
            ]);
            $this->contractCreateResource->save($contractCreate);
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
