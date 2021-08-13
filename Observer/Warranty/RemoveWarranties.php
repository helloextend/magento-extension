<?php


namespace Extend\Warranty\Observer\Warranty;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Extend\Warranty\Model\Product\Type as WarrantyType;
use Magento\Checkout\Helper\Cart;

class RemoveWarranties implements ObserverInterface
{
    /**
     * @var Cart
     */
    protected $cart;

    public function __construct
    (
        Cart $cart
    )
    {
        $this->cart = $cart;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var $item \Magento\Quote\Model\Quote\Item */
        $item = $observer->getEvent()->getQuoteItem();
        if ($item->getProductType() !== WarrantyType::TYPE_CODE) {
            $sku = $item->getSku();

            $quote = $this->cart->getQuote();
            $items = $quote->getAllItems();

            $removeWarranty = true;
            foreach ($items as $item) {
                if ($item->getSku() === $sku) {
                    $removeWarranty = false;
                    break;
                }
            }

            if ($removeWarranty) {
                foreach ($items as $item) {
                    if ($item->getProductType() === WarrantyType::TYPE_CODE &&
                        $item->getOptionByCode('associated_product')->getValue() === $sku) {

                        $quote->removeItem($item->getItemId());
                    }
                }
            }
        }
    }
}
