<?php

namespace Extend\Warranty\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Extend\Warranty\Model\Api\Sync\Leads\LeadsRequest;
use Extend\Warranty\Model\Api\Request\LeadBuilder;
use Extend\Warranty\Model\Api\Sync\Offers\OffersRequest;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class Leads
{
    /**
     * @var LeadsRequest
     */
    protected $leadsRequest;

    /**
     * @var LeadBuilder
     */
    protected $leadBuilder;

    /**
     * @var OffersRequest
     */
    protected $offersRequest;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct
    (
        LeadsRequest $leadsRequest,
        LeadBuilder $leadBuilder,
        OffersRequest $offersRequest,
        LoggerInterface $logger
    )
    {
        $this->leadsRequest = $leadsRequest;
        $this->leadBuilder = $leadBuilder;
        $this->offersRequest = $offersRequest;
        $this->logger = $logger;
    }

    /**
     * @param $itemSku
     * return array
     */
    public function getOffers($itemSku) {
        $offers = $this->offersRequest->consult($itemSku);
        if (!empty($offers) && isset($offers['plans'])
            && is_array($offers['plans']) && count($offers['plans']) >= 1) {
            return $offers['plans'];
        }
        return [];
    }

    /**
     * @param $itemSky
     */
    public function hasOffers($itemSku) {
        $offerPlans = $this->getOffers($itemSku);

        if (
            !empty($offerPlans)
            && is_array($offerPlans)
            && count($offerPlans) >= 1
        ) {
            return true;
        }
        return false;
    }

    /**
     * $param $order
     * $param $item
     */
    public function createLead($order, $item) {
        $lead = '';
        try {
            $lead =  $this->leadsRequest->create(
                $this->leadBuilder->prepareInfo($order, $item)
            );
        } catch(\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }

        return empty($lead) ? '' : $lead;
    }
}