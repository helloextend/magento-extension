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

namespace Extend\Warranty\Model;

use Extend\Warranty\Model\Api\Sync\Leads\LeadsRequest;
use Extend\Warranty\Model\Api\Request\LeadBuilder;
use Extend\Warranty\Model\Api\Sync\Offers\OffersRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Leads
 */
class Leads
{
    /**
     * Leads Request
     *
     * @var LeadsRequest
     */
    private $leadsRequest;

    /**
     * Lead Builder
     *
     * @var LeadBuilder
     */
    private $leadBuilder;

    /**
     * Offers Request
     *
     * @var OffersRequest
     */
    private $offersRequest;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Leads constructor
     *
     * @param LeadsRequest $leadsRequest
     * @param LeadBuilder $leadBuilder
     * @param OffersRequest $offersRequest
     * @param LoggerInterface $logger
     */
    public function __construct(
        LeadsRequest $leadsRequest,
        LeadBuilder $leadBuilder,
        OffersRequest $offersRequest,
        LoggerInterface $logger
    ) {
        $this->leadsRequest = $leadsRequest;
        $this->leadBuilder = $leadBuilder;
        $this->offersRequest = $offersRequest;
        $this->logger = $logger;
    }

    /**
     * Get offers
     *
     * @param string $sku
     * @return array
     */
    public function getOffers(string $sku): array
    {
        $offers = $this->offersRequest->consult($sku);

        return isset($offers['plans']) && is_array($offers['plans']) ? $offers['plans'] : [];
    }

    /**
     * Check if product has offers
     *
     * @param string $sku
     * @return bool
     */
    public function hasOffers(string $sku): bool
    {
        $offerInformation = $this->getOffers($sku);
        $recommended = $offerInformation['recommended'] ?? '';

        return $recommended
            && isset($offerInformation[$recommended])
            && is_array($offerInformation[$recommended])
            && !empty($offerInformation[$recommended]);
    }

    /**
     * Create lead
     *
     * @param OrderInterface $order
     * @param OrderItemInterface $item
     * @return string
     */
    public function createLead(OrderInterface $order, OrderItemInterface $item): string
    {
        $lead = '';
        try {
            $leadPayload = $this->leadBuilder->prepareInfo($order, $item);
            if (!empty($leadPayload)) {
                $lead = $this->leadsRequest->create($leadPayload);
            }
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $lead;
    }
}
