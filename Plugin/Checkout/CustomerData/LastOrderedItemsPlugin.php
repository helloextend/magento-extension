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

use \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use \Extend\Warranty\Model\Product\Type as WarrantyProductType;

class LastOrderedItemsPlugin
{
    /**
     * @var OrderItemCollectionFactory
     */
    protected $orderItemCollectionFactory;

    /**
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     */
    public function __construct(OrderItemCollectionFactory $orderItemCollectionFactory)
    {
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed
     */
    public function afterGetSectionData($subject, $result)
    {
        $orderItemCollection = $this->orderItemCollectionFactory->create();

        if (isset($result['items'])) {
            $itemIds = array_column($result['items'], 'id');
            $orderItemCollection->addFieldToFilter('item_id', $itemIds);

            foreach ($result['items'] as $key => $item) {
                if (
                    $orderItem = $orderItemCollection->getItemById($item['id'])
                ) {
                    if ($orderItem->getProductType() == WarrantyProductType::TYPE_CODE) {
                        unset($result['items'][$key]);
                    }
                }
            }
        }

        $result['items'] = array_values($result['items']);

        return $result;
    }
}