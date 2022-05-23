<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Controller\Adminhtml\Products\Sync;

use Extend\Warranty\Model\ProductSyncFlag;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\FlagManager;

/**
 * Class ResetFlag
 *
 * Reset the flag indicating that the products are being synchronized
 */
class ResetFlag extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Extend_Warranty::product_manual_sync';

    /**
     * Flag Manager Model
     *
     * @var FlagManager
     */
    private $flagManager;

    /**
     * ResetFlag constructor
     *
     * @param Context $context
     * @param FlagManager $flagManager
     */
    public function __construct(
        Context $context,
        FlagManager $flagManager
    ) {
        $this->flagManager = $flagManager;
        parent::__construct($context);
    }

    /**
     * Reset product sync flag
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $this->flagManager->deleteFlag(ProductSyncFlag::FLAG_NAME);

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData([
            'status' => true,
            'message' => __('Product sync process has been stopped.'),
        ]);

        return $resultJson;
    }
}
