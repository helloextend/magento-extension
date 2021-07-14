<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

declare(strict_types=1);

namespace Extend\Warranty\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer;
use Extend\Warranty\Model\Product\Type as WarrantyType;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Extend\Warranty\Model\WarrantyContract;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Class CreateContract
 */
class CreateContract implements ObserverInterface
{
    /**
     * Product Repository Interface
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Warranty Contract
     *
     * @var WarrantyContract
     */
    private $warrantyContract;

    /**
     * DataHelper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * LoggerInterface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CreateContract constructor
     *
     * @param ProductRepositoryInterface $productRepository
     * @param WarrantyContract $warrantyContract
     * @param LoggerInterface $logger
     * @param DataHelper $dataHelper
     */
    public function __construct (
        ProductRepositoryInterface $productRepository,
        WarrantyContract $warrantyContract,
        DataHelper $dataHelper,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->warrantyContract = $warrantyContract;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
    }

    /**
     * Create warranty contract for order item if item is invoiced
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        if ($this->dataHelper->isExtendEnabled() && $this->dataHelper->isWarrantyContractEnabled()) {
            $event = $observer->getEvent();
            $invoice = $event->getInvoice();

            $warranties = [];
            foreach ($invoice->getAllItems() as $invoiceItem) {
                $product = $this->getProduct((int)$invoiceItem->getProductId());
                if ($product && $product->getTypeId() === WarrantyType::TYPE_CODE) {
                    $orderItem = $invoiceItem->getOrderItem();
                    $warranties[$orderItem->getId()] = $orderItem;
                }
            }

            if (!empty($warranties)) {
                $order = $invoice->getOrder();
                $this->warrantyContract->createContract($order, $warranties);
            }
        }
    }

    /**
     * Get product
     *
     * @param int $productId
     * @return ProductInterface|null
     */
    private function getProduct(int $productId): ?ProductInterface
    {
        try {
            $product = $this->productRepository->getById($productId);
        } catch (LocalizedException $exception) {
            $product = null;
        }

        return $product;
    }
}
