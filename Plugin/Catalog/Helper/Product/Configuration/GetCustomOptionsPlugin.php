<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Plugin\Catalog\Helper\Product\Configuration;

use Magento\Catalog\Helper\Product\Configuration;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Extend\Warranty\Model\Product\Type;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class GetCustomOptionsPlugin
 *
 * GetCustomOptionsPlugin plugin
 */
class GetCustomOptionsPlugin
{
    /**
     * Set custom options for warranty
     *
     * @param Configuration $subject
     * @param array $result
     * @param ItemInterface $item
     * @return array
     */

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * Constructor
     *
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }


    public function afterGetCustomOptions(Configuration $subject, array $result, ItemInterface $item): array
    {
        $product = $item->getProduct();
        if ($product && $product->getTypeId() === Type::TYPE_CODE) {
            $customOptions = [];

            $associatedProductNameOption = $product->getCustomOption(Type::ASSOCIATED_PRODUCT_NAME);
            if ($associatedProductNameOption && $associatedProductNameOption->getValue()) {
                $associatedProductNameLabel = Type::ASSOCIATED_PRODUCT_NAME_LABEL;
                $customOptions[] = [
                    'label' => __($associatedProductNameLabel),
                    'value' => $associatedProductNameOption->getValue(),
                ];
            }

            $associatedProductOption = $product->getCustomOption(Type::ASSOCIATED_PRODUCT);
            if ($associatedProductOption && $associatedProductOption->getValue()) {
                $associatedProductLabel = Type::ASSOCIATED_PRODUCT_LABEL;
                $customOptions[] = [
                    'label' => __($associatedProductLabel),
                    'value' => $associatedProductOption->getValue(),
                ];
            }

            $warrantyTermOption = $product->getCustomOption(Type::TERM);
            if ($warrantyTermOption && $warrantyTermOption->getValue()) {
                $warrantyTerm = (int)$warrantyTermOption->getValue() / 12;
                $optionValue = ($warrantyTerm > 1) ? $warrantyTerm . ' years' : $warrantyTerm . ' year';
                $termLabel = Type::TERM_LABEL;
                $customOptions[] = [
                    'label' => __($termLabel),
                    'value' => $optionValue,
                ];
            }

            //Custom option parent order ID (this is displayed in the cart, so use Increment ID)
            $parentOrderIdOption = $product->getCustomOption(Type::ASSOCIATED_PARENT_ORDER_ID);
            if ($parentOrderIdOption && $parentOrderIdOption->getValue()) {
                $parentOrderId      = (int) $parentOrderIdOption->getValue();
                $order              = $this->orderRepository->get($parentOrderId);
                $incrementId        = $order->getIncrementId();
                $parentOrderLabel   = Type::PARENT_ORDER_LABEL;
                $customOptions[]    = [
                    'label' => __($parentOrderLabel),
                    'value' => $incrementId,
                ];
            }

            $result = array_merge($result, $customOptions);
        }

        return $result;
    }
}
