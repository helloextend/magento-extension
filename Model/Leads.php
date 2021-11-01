<?php

namespace Extend\Warranty\Model;

use Extend\Warranty\Model\Api\Sync\Offers\OffersRequest;
use Psr\Log\LoggerInterface;

class Leads
{
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
        OffersRequest $offersRequest,
        LoggerInterface $logger
    )
    {
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
}
