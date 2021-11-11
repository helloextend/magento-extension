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

use Zend_Http_Client;
use Zend_Http_Response;

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
     * @param array $data
     * @return Zend_Http_Response
     */
    public function call(
        string $endpoint,
        string $method = Zend_Http_Client::GET,
        array $data = []
    ): Zend_Http_Response;

    /**
     * Test connection
     *
     * @return bool
     */
    public function testConnection(): bool;
}
