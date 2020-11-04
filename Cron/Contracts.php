<?php

namespace Extend\Warranty\Cron;

use Extend\Warranty\Model\Product\Type as WarrantyType;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Extend\Warranty\Model\WarrantyContract;
use Magento\Sales\Api\OrderRepositoryInterface;

class Contracts
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ResourceConnection
     */
    protected $connection;

    /**
     * @var WarrantyContract
     */
    protected $warrantyContract;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    public function __construct
    (
        ResourceConnection $connection,
        WarrantyContract $warrantyContract,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    )
    {
        $this->warrantyContract = $warrantyContract;
        $this->connection = $connection;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    public function execute()
    {
        $this->logger->info('Starting Extend Contracts Cron...');

        $connection = $this->connection->getConnection();
        $table = $this->connection->getTableName('sales_order_item');

        $select = $connection->select();
        $select->from($table, 'order_id')
            ->where('product_type = ?', WarrantyType::TYPE_CODE)
            ->where('contract_id is null');

        try {
            $orders = $connection->fetchAll($select);

            if (!empty($orders)) {
                foreach ($orders as $_order) {
                    /** @var \Magento\Sales\Model\Order */
                    $order = $this->orderRepository->get($_order["order_id"]);

                    if (!$order || !$order->hasInvoices()) {
                        continue;
                    }

                    $flag = false;
                    $warranties = [];
                    /** @var \Magento\Sales\Model\Order\Item $item */
                    foreach ($order->getAllItems() as $key => $item) {
                        if ($item->getProductType() == WarrantyType::TYPE_CODE) {
                            if (!$flag) {
                                $flag = 1;
                            }
                            $warranties[$key] = $item;
                        }
                    }

                    if ($flag) {
                        $this->warrantyContract->createContract($order, $warranties);
                    }
                }
            }

        } catch (\Exception $e) {
            $this->logger->error('Contracts cron sync error: ' . $e->getMessage());
        }
        $this->logger->info('Ending Extend Contracts Cron...');
    }
}