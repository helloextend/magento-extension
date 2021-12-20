<?php

namespace Extend\Warranty\Plugin;

use Magento\Catalog\Controller\Adminhtml\Product\NewAction;
use Magento\Framework\Controller\Result\RedirectFactory;
use Extend\Warranty\Helper\Data;
use Magento\Framework\Message\ManagerInterface;

class WarrantyAction
{
    /**
     * @var RedirectFactory
     */
    protected $redirectFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    public function __construct(
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager
    )
    {
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
    }
    public function aroundExecute(NewAction $subject, callable $proceed)
    {
        $typeId = $subject->getRequest()->getParam('type');

        if (in_array($typeId, Data::NOT_ALLOWED_TYPES)) {
            $this->messageManager->addError(__("Protection plan products of type 'warranty' cannot be created by admin."));
            return $this->redirectFactory->create()->setPath('catalog/product/index');
        }

        return $proceed();
    }
}