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

namespace Extend\Warranty\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * Interface RequestInterface
 */
interface RequestInterface
{
    /**
     * Set connector config
     *
     * @param string $apiUrl
     * @param string $storeId
     * @param string $apiKey
     * @throws LocalizedException
     */
    public function setConfig(string $apiUrl, string $storeId, string $apiKey);

    /**
     * Build url
     *
     * @param string $endpoint
     * @return string
     */
    public function buildUrl(string $endpoint): string;
}
