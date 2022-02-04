<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

declare(strict_types=1);

namespace Extend\Warranty\Plugin\Checkout\CustomerData;

use Magento\Framework\UrlInterface;
use Magento\Checkout\CustomerData\AbstractItem;
use Extend\Warranty\Model\Product\Type;
use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Helper\Tracking as TrackingHelper;

/**
 * Class AbstractItemPlugin
 */
class AbstractItemPlugin
{
    private $urlBuilder;

    private $dataHelper;

    private $trackingHelper;

    public function __construct(
        UrlInterface $urlBuilder,
        DataHelper $dataHelper,
        TrackingHelper $trackingHelper
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->dataHelper = $dataHelper;
        $this->trackingHelper = $trackingHelper;
    }

    /**
     * Set 'isWarranty' flag for product image
     *
     * @param AbstractItem $subject
     * @param array $result
     * @return array
     */
    public function afterGetItemData(AbstractItem $subject, array $result, $item): array
    {
        $test = $item;
        $result['product_image']['isWarranty'] = isset($result['product_type']) && $result['product_type'] === Type::TYPE_CODE;
        $result['is_can_add_warranty'] = true;
        $result['warranty_add_url'] = $this->getWarrantyAddUrl();
        $result['product_parent_id'] = $this->getParentId($item);

        return $result;
    }

    private function getWarrantyAddUrl()
    {
        return $this->urlBuilder->getUrl('warranty/cart/add');
    }

    private function getParentId($item)
    {
        return $item->getOptionByCode('simple_product') ? $item->getProductId() : '';
    }
}
