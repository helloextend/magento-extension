<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Extend_Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Block\Order\Item\Renderer;

use Magento\Sales\Api\Data\OrderItemInterface;
use \Extend\Warranty\ViewModel\Warranty as WarrantyViewModel;

/**
 * @method getViewModel()
 */
class LeadOfferRenderer extends \Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer
{
    public function _toHtml()
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
}