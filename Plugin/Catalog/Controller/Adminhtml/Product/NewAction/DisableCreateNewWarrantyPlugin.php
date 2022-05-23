<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Plugin\Catalog\Controller\Adminhtml\Product\NewAction;

use Extend\Warranty\Helper\Data;
use Magento\Catalog\Controller\Adminhtml\Product\NewAction;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\RedirectFactory as ResultRedirectFactory;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class DisableCreateNewWarrantyPlugin
 *
 * DisableCreateNewWarrantyPlugin plugin
 */
class DisableCreateNewWarrantyPlugin
{
    /**
     * Manager Interface
     *
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * Result Redirect Factory Model
     *
     * @var ResultRedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * DisableCreateNewWarrantyPlugin constructor
     *
     * @param ManagerInterface $messageManager
     * @param ResultRedirectFactory $resultRedirectFactory
     */
    public function __construct(
        ManagerInterface $messageManager,
        ResultRedirectFactory $resultRedirectFactory
    ) {
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    /**
     * Disable new warranty product creation
     *
     * @param NewAction $subject
     * @param callable $proceed
     * @return ResultInterface
     */
    public function aroundExecute(NewAction $subject, callable $proceed): ResultInterface
    {
        $request = $subject->getRequest();
        $type = $request->getParam('type');

        if ($type && in_array($type, Data::NOT_ALLOWED_TYPES)) {
            $this->messageManager->addErrorMessage(
                __("Protection plan products of type 'warranty' cannot be created by admin.")
            );

            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('catalog/product/index');

            return $resultRedirect;
        }

        return $proceed();
    }
}
