<?php

namespace Extend\Warranty\Api;
use Zend_Http_Response;

interface ConnectorInterface
{
    public function initClient(): void;

    public function call(
        string $endpoint,
        string $method = \Zend_Http_Client::GET,
        array $data = null
    ): Zend_Http_Response;

    //Call for plugin of Cesar
    public function testConnection(): bool;

    public function simpleCall(
        string $endpoint
    ): string;
}