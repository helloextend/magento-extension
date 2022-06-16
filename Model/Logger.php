<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model;

use Monolog\Handler\HandlerInterface;
use Magento\Framework\Logger\Monolog;
use Extend\Warranty\Helper\Api\Data as DataHelper;

/**
 * Class Logger
 *
 * Warranty Logger Model
 */
class Logger extends Monolog
{
    /**
     * Warranty Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Logger constructor
     *
     * @param string $name
     * @param DataHelper $dataHelper
     * @param HandlerInterface[] $handlers
     * @param callable[] $processors
     */
    public function __construct(
        $name,
        DataHelper $dataHelper,
        array $handlers = [],
        array $processors = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($name, $handlers, $processors);
    }
}
