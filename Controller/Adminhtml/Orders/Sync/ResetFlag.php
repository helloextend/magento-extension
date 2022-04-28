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

namespace Extend\Warranty\Controller\Adminhtml\Orders\Sync;

use Extend\Warranty\Model\HistoricalOrdersSyncFlag;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\FlagManager;

/**
 * Class ResetFlag
 */
class ResetFlag extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Extend_Warranty::orders_manual_sync';

    /**
     * Flag Manager
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
     * Reset historical orders sync flag
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $this->flagManager->deleteFlag(HistoricalOrdersSyncFlag::FLAG_NAME);

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData([
            'status' => true,
            'message' => __('Historical orders sync process has been stopped.'),
        ]);

        return $resultJson;
    }
}
