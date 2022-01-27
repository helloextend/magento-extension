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

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Api\Sync\Lead\LeadsRequest as ApiLeadModel;
use Extend\Warranty\Model\Api\Request\LeadBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Leads
 */
class Leads
{
    /**
     * Api Lead Model
     *
     * @var ApiLeadModel
     */
    private $apiLeadBuilder;

    /**
     * Lead Builder
     *
     * @var LeadBuilder
     */
    private $leadBuilder;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Leads constructor
     *
     * @param ApiLeadModel $apiLeadBuilder
     * @param LeadBuilder $leadBuilder
     * @param LoggerInterface $logger
     * @param DataHelper $dataHelper
     */
    public function __construct(
        ApiLeadModel $apiLeadBuilder,
        LeadBuilder $leadBuilder,
        LoggerInterface $logger,
        DataHelper $dataHelper
    ) {
        $this->apiLeadBuilder = $apiLeadBuilder;
        $this->leadBuilder = $leadBuilder;
        $this->logger = $logger;
        $this->dataHelper = $dataHelper;
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
            $storeId = $item->getStoreId();

            $apiUrl = $this->dataHelper->getApiUrl(ScopeInterface::SCOPE_STORES, $storeId);
            $apiStoreId = $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId);
            $apiKey = $this->dataHelper->getApiKey(ScopeInterface::SCOPE_STORES, $storeId);

            $leadPayload = $this->leadBuilder->preparePayload($order, $item);
            if (!empty($leadPayload)) {
                $this->apiLeadBuilder->setConfig($apiUrl, $apiStoreId, $apiKey);
                $lead = $this->apiLeadBuilder->create($leadPayload);
            }
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $lead;
    }
}
