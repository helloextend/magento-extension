<?php
namespace Extend\Warranty\Model;

use Magento\Checkout\Model\Cart;

class Normalizer
{
    public function normalize(Cart $cart)
    {
        //split cart items from products and warranties
        $warranties = [];
        $products = [];
        foreach ($cart->getItems() as $item) {
            if ($item->getProductType() === 'warranty') {
                $warranties[$item->getItemId()] = $item;
            } else {
                $products[] = $item;
            }
        }

        //Loop products to see if their qty is different from the warranty qty and adjust both to max
        foreach ($products as $item) {
            $sku = $item->getSku();
            foreach ($warranties as $warrantyitem) {
                if ($warrantyitem->getOptionByCode('associated_product')->getValue() == $sku &&
                    ($item->getProductType() == 'configurable'  || is_null($item->getOptionByCode('parent_product_id')))) {
                    if ($warrantyitem->getQty() <> $item->getQty()) {
                        if ($item->getQty()>0) {
                            //Update Warranty QTY
                            $warrantyitem->setQty($item->getQty());
                        } else {
                            //Remove both product and warranty
                            $cart->removeItem($warrantyitem->getItemId());
                            $cart->removeItem($item->getItemId());
                        }
                    }
                }
            }
        }
    }
}