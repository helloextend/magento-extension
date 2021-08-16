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

namespace Extend\Warranty\Model\Config\Backend;

use Extend\Warranty\Model\Config\Source\Frequency;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Exception;

/**
 * Class ContractCreationCronSchedule
 */
class ContractCreationCronSchedule extends Value
{
    /**
     * Cron expression configuration path
     */
    const CRON_STRING_PATH = 'crontab/default/jobs/extend_warranty_create_warranty_contract/schedule/cron_expr';

    /**
     * Config Interface
     *
     * @var ConfigInterface
     */
    private $configResource;

    /**
     * ContractCreationCronSchedule constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ConfigInterface $configResource
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ConfigInterface $configResource,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configResource = $configResource;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Set cron schedule
     *
     * @return ContractCreationCronSchedule
     * @throws Exception
     */
    public function afterSave(): ContractCreationCronSchedule
    {
        $time = $this->getData('groups/contracts/groups/cron/fields/time/value');
        $frequency = $this->getData('groups/contracts/groups/cron/fields/frequency/value');

        switch ($frequency) {
            case Frequency::CRON_EVERY_MINUTE:
                $cronExprArray = ['*/' . intval($time[1]), '*', '*', '*', '*'];
                break;
            case Frequency::CRON_EVERY_HOUR:
                $cronExprArray = [0, '*/' . intval($time[0]), '*', '*', '*'];
                break;
            default:
                $cronExprArray = [
                    intval($time[1]),
                    intval($time[0]),
                    $frequency == Frequency::CRON_MONTHLY ? '1' : '*',
                    '*',
                    $frequency == Frequency::CRON_WEEKLY ? '1' : '*',
                ];
                break;
         }

        $cronExprString = join(' ', $cronExprArray);

        try {
            $this->configResource->saveConfig(
                self::CRON_STRING_PATH,
                $cronExprString
            );
        } catch (Exception $exception) {
            throw new Exception(__('We were unable to save the cron expression.'));
        }

        return parent::afterSave();
    }
}
