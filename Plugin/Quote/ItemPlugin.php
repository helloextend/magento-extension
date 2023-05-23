<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Plugin\Quote;

use Extend\Warranty\Model\LeadInfo;

class ItemPlugin
{

    /**
     * @var LeadInfo
     */
    protected $leadInfo;

    /**
     * @param LeadInfo $leadInfo
     */
    public function __construct(
        LeadInfo $leadInfo
    )
    {
        $this->leadInfo = $leadInfo;
    }

    public function afterCheckData($quoteItem, $result)
    {
        $qty = $quoteItem->getData('qty');

        if (!$quoteItem->getExtensionAttributes()
            || !$quoteItem->getExtensionAttributes()->getLeadToken()) {
            return;
        }

        $leadToken = $quoteItem->getExtensionAttributes()->getLeadToken();
        $leadInfo = $this->leadInfo->getLeadInfo($leadToken);

        if ($qty > $leadInfo->getLeftQty()) {
            $quoteItem->setHasError(true);
            $quoteItem->setMessage(__(
                'Warranty Quantity Exceeds Purchased Products Qty.
                       Qty left to purchase: %1
                ', $leadInfo->getLeftQty()));
        }

        return $result;
    }
}