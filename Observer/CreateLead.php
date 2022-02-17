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

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Config\Source\CreateContractApi;
use Extend\Warranty\Model\Leads as LeadModel;
use Extend\Warranty\Model\Offers as OfferModel;
use Extend\Warranty\Model\Orders as ExtendOrder;
use Extend\Warranty\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CreateLead
 */
class CreateLead implements ObserverInterface
{
    /**
     * Order Item Repository Interface
     *
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * Offer Model
     *
     * @var OfferModel
     */
    private $offerModel;

    /**
     * Lead Model
     *
     * @var LeadModel
     */
    private $leadModel;

    private $extendOrder;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CreateLead constructor
     *
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param OfferModel $offerModel
     * @param LeadModel $leadModel
     * @param DataHelper $dataHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderItemRepositoryInterface $orderItemRepository,
        OfferModel $offerModel,
        LeadModel $leadModel,
        ExtendOrder $extendOrder,
        DataHelper $dataHelper,
        LoggerInterface $logger
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->offerModel = $offerModel;
        $this->leadModel = $leadModel;
        $this->extendOrder = $extendOrder;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
    }

    /**
     * Create a lead
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();
        $storeId = $order->getStoreId();

        if (
            $this->dataHelper->isExtendEnabled(ScopeInterface::SCOPE_STORES, $storeId)
            && $this->dataHelper->isLeadEnabled($storeId)
        ) {
            $productItems = $warrantyItems = [];
            foreach ($order->getAllItems() as $orderItem) {
                if ($orderItem->getProductType() === Type::TYPE_CODE) {
                    $warrantyItems[] = $orderItem;
                } else {
                    $productItems[] = $orderItem;
                }
            }

            if (count($productItems) == 0 && count($warrantyItems) > 0) {
                $leadToken = [];
                foreach ($warrantyItems as $warrantyItem) {
                    try {
                        if (array_key_exists('leadToken',$warrantyItem->getProductOptionByCode('info_buyRequest'))) {
                            $leadToken[] = $warrantyItem->getProductOptionByCode('info_buyRequest')['leadToken'];
                            if ($leadToken) {
                                $warrantyItem->setLeadToken(json_encode($leadToken));
                            }
                        }
                    } catch (LocalizedException $exception) {
                        $this->logger->error('Error during lead saving. ' . $exception->getMessage());
                    }
                }
            }

            foreach ($productItems as &$productItem) {
                $sku = $productItem->getSku();
                $hasWarranty = false;
                foreach ($warrantyItems as $warrantyItem) {
                    $associatedSku = $warrantyItem->getProductOptionByCode(Type::ASSOCIATED_PRODUCT);
                    if (
                        $associatedSku
                        && $sku === $associatedSku
                        && (
                            $productItem->getProductType() === Configurable::TYPE_CODE
                            || is_null($productItem->getProductOptionByCode('parent_product_id'))
                        )
                    ) {
                        $hasWarranty = true;
                        break;
                    }
                }

                if (!$hasWarranty) {
                    if ($this->dataHelper->isContractCreateModeScheduled(ScopeInterface::SCOPE_STORES, $storeId) == CreateContractApi::CONTACTS_API) {
                        $hasOffers = $this->offerModel->orderItemHasOffers($productItem);
                        if ($hasOffers) {
                            try {
                                $leadToken = $this->leadModel->createLead($order, $productItem);
                                if ($leadToken) {
                                    $productItem->setLeadToken($leadToken);
                                    if ($order->getId()) {
                                        $this->orderItemRepository->save($productItem);
                                    }
                                }
                            } catch (LocalizedException $exception) {
                                $this->logger->error('Error during lead creation. ' . $exception->getMessage());
                            }
                        }
                    } elseif ($this->dataHelper->isContractCreateModeScheduled(ScopeInterface::SCOPE_STORES, $storeId) == CreateContractApi::ORDERS_API) {
                        try {
                            $leadToken = $this->extendOrder->createOrder($order, $productItem, intval($productItem->getQtyOrdered()), ExtendOrder::LEAD);
                            if ($leadToken) {
                                $productItem->setLeadToken($leadToken);
                                if ($order->getId()) {
                                    $this->orderItemRepository->save($productItem);
                                }
                            }
                        } catch (LocalizedException $exception) {
                            $this->logger->error('Error during lead creation. ' . $exception->getMessage());
                        }
                    }
                }
            }
        }
    }
}
