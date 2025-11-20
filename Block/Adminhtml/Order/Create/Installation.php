<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */


namespace Extend\Warranty\Block\Adminhtml\Order\Create;

use Magento\Backend\Block\Template;

class Installation extends Template
{
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */

    private $quote;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        ?\Magento\Backend\Model\Session\Quote $quote = null,
        array $data = []
    ){
        $this->quote =  $quote ?? \Magento\Framework\App\ObjectManager::getInstance()
                            ->get(\Magento\Backend\Model\Session\Quote::class);
        parent::__construct($context, $data);
    }

   /**
    * Get store ID from the admin session quote (current order being created).
    *
    * @return int|null
    */
    public function getCurrentStore()
    {
        return $this->quote->getStoreId();
    }
}