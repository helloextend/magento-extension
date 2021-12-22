<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

declare(strict_types=1);

namespace Extend\Warranty\ViewModel;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Class OrderExport
 */
class OrderExport implements ArgumentInterface
{
    /**
     * Module Manager
     *
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * Product Repository Interface
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * OrderExport constructor
     * 
     * @param ModuleManager $moduleManager
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ModuleManager $moduleManager,
        ProductRepositoryInterface $productRepository
    ) {
        $this->moduleManager = $moduleManager;
        $this->productRepository = $productRepository;
    }

    /**
     * Check if module enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->moduleManager->isEnabled('Wyomind_OrdersExportTool');
    }

    /**
     * Get export source
     * 
     * @param OrderItemInterface $item
     * @return int
     */
    public function getExportTo(OrderItemInterface $item): int
    {
        $exportSource = $item->getExportTo();
        if (!$exportSource) {
            $productId = (int)$item->getProductId();
            
            try {
                $product = $this->productRepository->getById($productId);
                $exportToCustomAttr = $product->getCustomAttribute('export_to');
                $exportSource = $exportToCustomAttr && $exportToCustomAttr->getValue() 
                    ? $exportToCustomAttr->getValue()
                    : '';                    
            } catch (LocalizedException $exception) {
                $exportSource = '';
            }
        }
        
        return (int)$exportSource;
    }

    /**
     * Get profiles data as array
     *
     * @param LayoutInterface $layout
     * @return array
     */
    public function getProfilesAsArray(LayoutInterface $layout): array
    {
        $profilesData = [0 => __('-- No profile --')];
        $block = $layout->getBlock('warranty_order_items_renderer');
        if ($block) {
            $profiles = $block->getData('oet_profiles') ?? [];
            foreach ($profiles as $profile) {
                if ($profile->getName()) {
                    $profilesData[$profile->getId()] = $profile->getName();
                }
            }
        }

        return $profilesData;
    }
}