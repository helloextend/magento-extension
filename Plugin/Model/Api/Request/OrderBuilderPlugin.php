<?php

namespace Extend\Warranty\Plugin\Model\Api\Request;

use Extend\Warranty\Model\Api\Request\OrderBuilder;

/**
 * Class OrderBuilderPlugin
 *
 * This plugin reformat customer phone number to 10 numeric chars format
 */
class OrderBuilderPlugin
{
    /**
     * @param OrderBuilder $subject
     * @param array $payload
     * @return array
     */
    public function afterPreparePayload(OrderBuilder $subject, array $payload)
    {
        if (isset($payload['customer']['phone']) && strlen($payload['customer']['phone']) > 10) {
            $payload['customer']['phone'] = substr(preg_replace("/[^0-9]/", "", $payload['customer']['phone']), -10);
        }

        return $payload;
    }
}
