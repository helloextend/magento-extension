<?php


namespace Extend\Warranty\Model\Api;


use Extend\Warranty\Api\Data\UrlBuilderInterface;
use Extend\Warranty\Helper\Api\Data as Config;

class UrlBuilder implements UrlBuilderInterface
{

    const DS = DIRECTORY_SEPARATOR;

    protected $config;

    protected $uri;


    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function build(): string
    {
        $baseUrl = $this->config->isExtendLive() ?
            static::LIVE_URL :
            static::SANDBOX_URL;

        return rtrim($baseUrl, static::DS) .
            static::DS .
            ltrim($this->uri, static::DS);
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return (string) $this->uri;
    }

    /**
     * @param string $uri
     * @return $this
     */
    public function setUri(string $uri): UrlBuilderInterface
    {
        $this->uri = $uri;

        return $this;
    }
}
