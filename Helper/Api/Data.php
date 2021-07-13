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

namespace Extend\Warranty\Helper\Api;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
    /**
     * Warranty config XML paths
     */
    const BASEPATH = 'warranty/authentication/';
    const ENABLE_PATH = 'warranty/enableExtend/';

    /**
     * Get value
     *
     * @param string $field
     * @return string
     */
    public function getValue(string $field): string
    {
        $path = self::BASEPATH . $field;

        return (string)$this->scopeConfig->getValue($path);
    }

    /**
     * Check if extend enabled
     *
     * @return bool
     */
    public function isExtendEnabled(): bool
    {
        $path = self::ENABLE_PATH . 'enable';

        return $this->scopeConfig->isSetFlag($path);
    }

    /**
     * Check if Extend live
     *
     * @return bool
     */
    public function isExtendLive(): bool
    {
        $path = self::BASEPATH . 'auth_mode';

        return $this->scopeConfig->isSetFlag($path);
    }

    /**
     * Check if cart balance enabled
     *
     * @return bool
     */
    public function isBalancedCart(): bool
    {
        $path = self::ENABLE_PATH . 'enableBalance';

        return $this->scopeConfig->isSetFlag($path);
    }

    /**
     * Check if display offers enabled
     *
     * @return bool
     */
    public function isDisplayOffersEnabled(): bool
    {
        $path = self::ENABLE_PATH. 'enableCartOffers';

        return $this->scopeConfig->isSetFlag($path);
    }

    /**
     * Check if refund enabled
     *
     * @return bool
     */
    public function isRefundEnabled(): bool
    {
        $path = self::ENABLE_PATH. 'enableRefunds';

        return $this->scopeConfig->isSetFlag($path);
    }

    /**
     * Check if warranty contract creation for order item is enabled
     *
     * @return bool
     */
    public function isWarrantyContractEnabled(): bool
    {
        $path = self::ENABLE_PATH. 'warranty_contract_enabled';

        return $this->scopeConfig->isSetFlag($path);
    }

    /**
     * Check if a refund should be created automatically when credit memo is created
     *
     * @return bool
     */
    public function isAutoRefundEnabled(): bool
    {
        $path = self::ENABLE_PATH. 'auto_refund_enabled';

        return $this->scopeConfig->isSetFlag($path);
    }
}
