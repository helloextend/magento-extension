<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Extend\Warranty\Helper\Data as Helper;
use Exception;

/**
 * Class ContractCreationCronSchedule
 *
 * ContractCreationCronSchedule Backend Model
 */
class ContractCreationCronSchedule extends Value
{
    /**
     * Warranty Helper
     *
     * @var Helper
     */
    private $helper;

    /**
     * ContractCreationCronSchedule constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param Helper $helper
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Helper $helper,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        $frequency = $this->getData('groups/contracts/groups/cron/fields/frequency/value');
        if (!$this->helper->isCronExpressionValid($frequency)) {
            throw new Exception(__('We can\'t save the cron expression.')); //phpcs:ignore
        }

        return parent::beforeSave();
    }
}
