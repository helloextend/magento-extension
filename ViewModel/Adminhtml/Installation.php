<?php

namespace Extend\Warranty\ViewModel\Adminhtml;

use Extend\Warranty\Helper\Api\Data as DataHelper;
use \Magento\Backend\Model\Session\Quote as SessionQuote;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class Installation extends \Extend\Warranty\ViewModel\Installation
{

    private $session;

    public function __construct(
        DataHelper     $dataHelper,
        JsonSerializer $jsonSerializer,
        SessionQuote   $sessionQuote
    )
    {
        parent::__construct($dataHelper, $jsonSerializer);
        $this->session = $sessionQuote;
    }

    public function getCurrentStoreId()
    {
        return $this->session->getStoreId();
    }
}
