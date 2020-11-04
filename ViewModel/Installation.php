<?php


namespace Extend\Warranty\ViewModel;


use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Extend\Warranty\Helper\Api\Data;
use Extend\Warranty\Model\Keys;
use Extend\Warranty\Model\Api\Connector;
use Magento\Framework\Serialize\Serializer\Json;

class Installation implements ArgumentInterface
{
    const DEMO = 'demo';

    const LIVE = 'live';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Data
     */
    protected $data;

    /**
     * @var Keys
     */
    protected $keys;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var Connector
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $enable;

    /**
     * @var string
     */
    protected $storeId;

    public function __construct
    (
        StoreManagerInterface $storeManager,
        Data $data,
        Keys $keys,
        Connector $connection,
        Json $jsonSerializer
    )
    {
        $this->storeManager = $storeManager;
        $this->data = $data;
        $this->keys = $keys;
        $this->connection = $connection;
        $this->jsonSerializer = $jsonSerializer;
    }

    public function prepareBlockData()
    {
        if ($this->data->isExtendEnabled()) {
            $this->enable = true;
            $keys = $this->keys->getKeys();
            $this->storeId = $keys['store_id'];
        } else {
            $this->enable = false;
        }
    }

    public function getJsMode()
    {
        return "https://sdk.helloextend.com/extend-sdk-client/v1/extend-sdk-client.min.js";
    }

    public function getJsonConfig()
    {

        $data = [
            'storeId' => (string)$this->getExtendStoreId(),
            'environment' => (string)$this->getExtendLive()? self::LIVE : self::DEMO,
        ];

        return $this->jsonSerializer->serialize($data);
    }

    public function getExtendEnable()
    {
        return isset($this->enable) ? $this->enable : $this->data->isExtendEnabled();
    }

    public function getExtendLive()
    {
        return $this->data->isExtendLive();
    }

    public function getExtendStoreId()
    {
        return isset($this->storeId) ? $this->storeId : null;
    }

}