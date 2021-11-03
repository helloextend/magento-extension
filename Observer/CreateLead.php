<?php

namespace Extend\Warranty\Observer;

use Extend\Warranty\Model\Product\Type as WarrantyType;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Extend\Warranty\Model\WarrantyContract;
use Extend\Warranty\Model\Leads;
use Extend\Warranty\Helper\Api\Data;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;


class CreateLead implements ObserverInterface
{
    protected $productRepository;
    protected $warrantyContract;
    protected $quoteRepository;
    protected $leads;
    protected $extendHelper;
    protected $logger;

    public function __construct
    (
        ProductRepositoryInterface $productRepository,
        WarrantyContract $warrantyContract,
        CartRepositoryInterface $quoteRepository,
        Leads $leads,
        Data $data,
        LoggerInterface $logger
    )
    {
        $this->productRepository = $productRepository;
        $this->warrantyContract = $warrantyContract;
        $this->leads = $leads;
        $this->quoteRepository = $quoteRepository;
        $this->extendHelper = $data;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        if ($this->extendHelper->isExtendEnabled() && $this->extendHelper->isLeadEnabled()) {
            $order = $observer->getEvent()->getOrder();
            $hasWarranty = false;

            //Check if there is a warranty
            foreach ($order->getAllItems() as $key => $item) {
                if (!$hasWarranty && $item->getProductType() == WarrantyType::TYPE_CODE) {
                    $hasWarranty = true;
                }
            }
            //If there is not warranties, check if there is available offers for leads
            if (false == $hasWarranty) {
                foreach ($order->getAllItems() as $key => $item) {
                    if ($item->getProductType() == 'configurable') {
                        continue;
                    }
                    $hasOffers = $this->leads->hasOffers($item->getSku());
                    if ($hasOffers) {
                        $leadToken = $this->leads->createLead($order, $item);

                        //Save Lead Token
                        if (!empty($leadToken)) {
                            $item->setLeadToken($leadToken);
                            if ($order->getId()) {
                                $item->save();
                            }
                        }
                    }
                }
            }
        }
    }
}