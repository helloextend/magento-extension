<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Extend_Warranty
 * @copyright   Copyright (c) 2023 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Block\Adminhtml\Order\View\Items\Renderer;

use Extend\Warranty\ViewModel\Warranty as WarrantyViewModel;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * @method getViewModel()
 */
class LeadOfferRenderer extends \Magento\Sales\Block\Adminhtml\Order\View
{
    /**
     * @return string
     */
    public function _toHtml(): string
    {
        /** @var WarrantyViewModel $viewModel */
        $viewModel = $this->getViewModel();

        /** @var OrderItemInterface $item */
        $item = $this->getItem();

        if (!$item || !$viewModel || !$viewModel->showLeadOffer($item)) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * @return OrderItemInterface | null
     */
    public function getItem(){
        $parentBlock = $this->getParentBlock();
        return $parentBlock ? $parentBlock->getItem() : null;
    }
}