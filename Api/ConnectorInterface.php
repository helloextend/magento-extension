<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Api;

use Extend\Warranty\Model\Api\Response;

/**
 * Interface ConnectorInterface
 */
interface ConnectorInterface
{
    /**
     * Send request
     *
     * @param string $endpoint
     * @param string $method
     * @param array $headers
     * @param array $data
     * @return Response
     */
    public function call(
        string $endpoint,
        string $method = "GET",
        array  $headers = [],
        array  $data = []
    ): Response;
}
