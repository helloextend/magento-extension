<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Api;

class Response
{

    /**
     * @var string
     */
    protected $body;

    /**
     * @var null
     */
    protected $headers = null;

    /**
     * @var integer
     */
    protected $statusCode;

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = (string)$body;
        return $this;
    }

    public function getRawBody()
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function getHeadersAsString()
    {
        $headers = $this->headers ?? [];
        $raw_headers = [];

        foreach ($headers as $name => $value) {
            $raw_headers[] = $name . ': ' .  $value;
        }

        return implode("\r\n", $raw_headers);
    }

    /**
     * @param [] $headers
     * @return $this
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->statusCode == 200;
    }

    /**
     * @param int $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = (int) $statusCode;
        return $this;
    }

    public function getStatus()
    {
        return $this->statusCode;
    }

}