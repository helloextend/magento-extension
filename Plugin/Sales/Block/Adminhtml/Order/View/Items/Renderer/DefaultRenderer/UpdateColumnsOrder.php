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

namespace Extend\Warranty\Plugin\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer;

use Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer;

/**
 * Class UpdateColumnsOrder
 */
class UpdateColumnsOrder
{
    /**
     * Update columns order
     *
     * @param DefaultRenderer $subject
     * @param array $result
     * @return array
     */
    public function afterGetColumns(DefaultRenderer $subject, array $result): array
    {
        if (!empty($result)) {
            if (isset($result['refund'])) {
                $refundColumn = $result['refund'];
                unset($result['refund']);
                $result = array_merge(
                    $result,
                    ['refund' => $refundColumn]
                );
            }
        }

        return $result;
    }
}
