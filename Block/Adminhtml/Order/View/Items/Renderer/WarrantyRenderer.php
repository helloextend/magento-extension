<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Block\Adminhtml\Order\View\Items\Renderer;

use Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer;
use Magento\Sales\Model\Order\Item;
use Magento\Backend\Block\Template\Context;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\Registry;
use Magento\GiftMessage\Helper\Message;
use Magento\Checkout\Helper\Data;
use Extend\Warranty\Helper\Api\Data as ExtendData;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\DataObject;
use Exception;

class WarrantyRenderer extends DefaultRenderer
{
    /**
     * @var ExtendData
     */
    protected $extendHelper;

    /**
     * Json Serializer
     *
     * @var JsonSerializer
     */
    private $serializer;

    /**
     * WarrantyRenderer constructor.
     *
     * @param Context $context
     * @param StockRegistryInterface $stockRegistry
     * @param StockConfigurationInterface $stockConfiguration
     * @param Registry $registry
     * @param Message $messageHelper
     * @param Data $checkoutHelper
     * @param ExtendData $extendHelper
     * @param JsonSerializer $serializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        StockRegistryInterface $stockRegistry, //phpcs:ignore
        StockConfigurationInterface $stockConfiguration,
        Registry $registry,
        Message $messageHelper,
        Data $checkoutHelper,
        ExtendData $extendHelper,
        JsonSerializer $serializer,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $stockRegistry,
            $stockConfiguration,
            $registry,
            $messageHelper,
            $checkoutHelper,
            $data
        );
        $this->extendHelper = $extendHelper;
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function getColumnHtml(DataObject $item, $column, $field = null)
    {
        if (!$this->extendHelper->isExtendEnabled() || !$this->extendHelper->isRefundEnabled()) {
            return parent::getColumnHtml($item, $column, $field);
        }
        $html = '';
        switch ($column) {
            case 'refund':
                if ($item->getStatusId() == Item::STATUS_INVOICED) {
                    $options = $item->getProductOptions();
                    if (isset($options['refund']) && $options['refund'] === false) {
                        if ($this->canDisplayContainer()) {
                            $html .= '<div id="' . $this->getHtmlId() . '">';
                        }
                        $html .= '<button type="button" class="action action-extend-refund"' .
                            " data-mage-init='{$this->getDataInit($item, $this->canShowPartial($item))}' >" .
                            "Request Refund</button>";
                        if ($this->canDisplayContainer()) {
                            $html .= '</div>';
                        }
                    } elseif (isset($options['refund']) && $options['refund'] === true) {
                        if ($this->canDisplayContainer()) {
                            $html .= '<div id="' . $this->getHtmlId() . '">';
                        }
                        $html .= '<button type="button" class="action action-extend-refund" disabled>Refunded</button>';
                        if ($this->canDisplayContainer()) {
                            $html .= '</div>';
                        }
                    } else {
                        $html .= '&nbsp;';
                    }
                } else {
                    $html .= '&nbsp;';
                }
                break;
            default:
                $html = parent::getColumnHtml($item, $column, $field);
        }
        return $html;
    }

    /**
     * Get Data Init
     *
     * @param Item $item
     * @param bool $isPartial
     *
     * @return string
     */
    private function getDataInit($item, $isPartial = false)
    {
        $contractIDData = $this->getContractIDData($item);

        if (empty($contractIDData)) {
            $contractID = $this->serializer->serialize([]);
        } else {
            $contractID = $item->getContractId();
        }
        $_elements = count($contractIDData);

        return '{"refundWarranty": {"url": "' . $this->getUrl('extend/contract/refund') .
            '", "contractId": ' . $contractID .
            ', "isPartial": "' . $isPartial . '"' .
            ', "maxRefunds": "' . $_elements . '"' .
            ', "itemId": "' . $item->getId() . '" }}';
    }

    /**
     * Can show partial
     *
     * @param Item $item
     *
     * @return bool
     */
    private function canShowPartial(Item $item)
    {
        $contractIDData = $this->getContractIDData($item);

        return (count($contractIDData) > 1);
    }

    /**
     * Get Html Id
     *
     * @return string
     */
    public function getHtmlId()
    {
        return 'return_order_item_' . $this->getItem()->getId();
    }

    /**
     * Get contract id data
     *
     * @param Item $item
     *
     * @return array
     */
    private function getContractIDData(Item $item)
    {
        $contractID = $item->getContractId();
        if (!empty($contractID)) {
            try {
                $contractIDData = $this->serializer->unserialize($contractID);
            } catch (Exception $exception) {
                $contractIDData = [];
            }
        } else {
            $contractIDData = [];
        }

        return $contractIDData;
    }
}
