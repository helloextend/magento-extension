<?php

namespace Extend\Warranty\Plugin\Block\Product\View\Type;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as SuperConfigurable;
use Magento\Framework\Serialize\Serializer\Json;

class Configurable
{
    /**
     * Json Serializer Model
     *
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * Configurable constructor.
     *
     * @param Json $jsonSerializer
     */
    public function __construct(Json $jsonSerializer)
    {
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Add skus to config
     *
     * @param SuperConfigurable $subject
     * @param string $result
     * @return string
     */
    public function afterGetJsonConfig(SuperConfigurable $subject, $result)
    {
        $jsonResult = $this->jsonSerializer->unserialize($result);

        $jsonResult['skus'] = [];
        foreach ($subject->getAllowProducts() as $simpleProduct) {
            $jsonResult['skus'][$simpleProduct->getId()] = $simpleProduct->getSku();
        }

        $result = $this->jsonSerializer->serialize($jsonResult);

        return $result;
    }
}
