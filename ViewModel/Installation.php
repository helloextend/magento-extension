<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\ViewModel;

use Extend\Warranty\Model\Config\Source\AuthMode;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use InvalidArgumentException;

/**
 * Class Installation
 */
class Installation implements ArgumentInterface
{
    /**
     * DataHelper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Json Serializer
     *
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * Installation constructor
     *
     * @param DataHelper $dataHelper
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        DataHelper $dataHelper,
        JsonSerializer $jsonSerializer
    ) {
        $this->dataHelper = $dataHelper;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Check if module enabled
     *
     * @return bool
     */
    public function isExtendEnabled(): bool
    {
        return $this->dataHelper->isExtendEnabled();
    }

    /**
     * Get JSON config
     *
     * @return string
     */
    public function getJsonConfig(): string
    {
        $jsonConfig = '';

        $storeId = $this->dataHelper->getStoreId();
        if ($storeId) {
            $config = [
                'storeId' => $storeId,
                'environment' => $this->dataHelper->isExtendLive() ? AuthMode::LIVE : AuthMode::DEMO,
            ];

            try {
                $jsonConfig = $this->jsonSerializer->serialize($config);
            } catch (InvalidArgumentException $exception) {
                $jsonConfig = '';
            }
        }

        return $jsonConfig;
    }

    /**
     * Get JS mode
     *
     * @return string
     */
    public function getJsMode(): string
    {
        return "https://sdk.helloextend.com/extend-sdk-client/v1/extend-sdk-client.min.js";
    }
}
