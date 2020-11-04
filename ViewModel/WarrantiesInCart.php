<?php


namespace Extend\Warranty\ViewModel;


use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Checkout\Model\Session;
use Extend\Warranty\Helper\Api\Data;
use Magento\Sales\Model\AdminOrder\Create as OrderCreate;

class WarrantiesInCart implements ArgumentInterface
{
    protected $checkoutSession;

    protected $helper;

    /**
     * @var OrderCreate
     */
    protected $orderCreate;

    public function __construct
    (
        Session $checkoutSession,
        Data $helper,
        OrderCreate $orderCreate
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
        $this->orderCreate = $orderCreate;
    }

    public function hasWarranty($sku, $fromAdmin = false)
    {
        if ($fromAdmin) {
            $quoteData =  $this->orderCreate->getQuote();
            foreach ($quoteData->getAllVisibleItems() as $item) {
                if ($item->getProductType() === 'warranty') {
                    if ($item->getOptionByCode('associated_product')->getValue() === $sku) {
                        return true;
                    }
                }
            }
            return false;
        } else {
            foreach ($this->checkoutSession->getQuote()->getAllVisibleItems() as $item) {
                if ($item->getProductType() === 'warranty') {
                    if ($item->getOptionByCode('associated_product')->getValue() === $sku) {
                        return true;
                    }
                }
            }
            return false;
        }

    }

    public function isDisplayOffersEnabled()
    {
        return $this->helper->isDisplayOffersEnabled();
    }

}