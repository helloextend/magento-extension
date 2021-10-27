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

namespace Extend\Warranty\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Extend\Warranty\Model\Leads as LeadModel;
use Magento\Framework\App\Helper\Context;

/**
 * Class Api
 */
class Api extends AbstractHelper
{
    /**
     * Lead Model
     *
     * @var LeadModel
     */
    private $leadModel;

    /**
     * Api constructor
     *
     * @param Context $context
     * @param LeadModel $leadModel
     */
    public function __construct(
        Context $context,
        LeadModel $leadModel
    ) {
        $this->leadModel = $leadModel;
        parent::__construct($context);
    }

    /**
     * Check if product has warranty offers
     *
     * @param string $productSku
     * @return bool
     */
    public function isProductHasOffers(string $productSku): bool
    {
        return $this->leadModel->hasOffers($productSku);
    }
}
