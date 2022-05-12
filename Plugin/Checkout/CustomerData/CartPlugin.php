<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Plugin\Checkout\CustomerData;

use Magento\Checkout\CustomerData\Cart;
use Magento\Quote\Model\Quote\Item;
use Magento\Framework\DataObject;

/**
 * Class CartPlugin
 *
 * Fix no visible products in minicart
 */
class CartPlugin extends Cart
{
    /**
     * Fix items in section data
     *
     * @param Cart $subject
     * @param array $sectionData
     *
     * @return array
     */
    public function afterGetSectionData(Cart $subject, array $sectionData)
    {
        $sectionData['items'] = $this->getRecentItems();

        return $sectionData;
    }

    /**
     * @inheritDoc
     */
    protected function getRecentItems()
    {
        $items = [];
        if (!$this->getSummaryCount()) {
            return $items;
        }

        foreach (array_reverse($this->getAllQuoteItems()) as $item) {
            /* @var $item Item */
            if (!$item->getProduct()->isVisibleInSiteVisibility()) {
                $product =  $item->getOptionByCode('product_type') !== null
                    ? $item->getOptionByCode('product_type')->getProduct()
                    : $item->getProduct();

                $products = $this->catalogUrl->getRewriteByProductStore([$product->getId() => $item->getStoreId()]);
                if (isset($products[$product->getId()])) {
                    $urlDataObject = new DataObject($products[$product->getId()]);
                    $product = $item->getProduct();
                    $product->setUrlDataObject($urlDataObject);
                }
            }
            $items[] = $this->itemPoolInterface->getItemData($item);
        }
        return $items;
    }
}
