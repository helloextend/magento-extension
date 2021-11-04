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

namespace Extend\Warranty\Api\Data;

/**
 * Interface UrlBuilderInterface
 */
interface UrlBuilderInterface
{
    /**
     * Build url
     *
     * @param string $endpoint
     * @return string
     */
    public function build(string $endpoint): string;
}
