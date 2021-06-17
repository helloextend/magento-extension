<?php

namespace Extend\Warranty\Observer;

use Extend\Warranty\Model\Product\Type as WarrantyType;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Extend\Warranty\Model\WarrantyContract;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;

class CreateContract implements ObserverInterface
{
    protected $productRepository;
    protected $warrantyContract;
    protected $quoteRepository;
    protected $logger;

    CONST REQUIRED_ORDER_STATE = 'complete';

    public function __construct
    (
        ProductRepositoryInterface $productRepository,
        WarrantyContract $warrantyContract,
        CartRepositoryInterface $quoteRepository,
        LoggerInterface $logger
    )
    {
        $this->productRepository = $productRepository;
        $this->warrantyContract = $warrantyContract;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $order = $observer->getEvent()->getOrder();

        if ($order instanceof \Magento\Framework\Model\AbstractModel) {
            if(self::REQUIRED_ORDER_STATE == $order->getState()) {
                $warranties = [];
                $flag = 0;

                foreach ($order->getAllItems() as $key => $item) {
                    /** @var \Magento\Sales\Model\Order\Item $item */
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

    }
}