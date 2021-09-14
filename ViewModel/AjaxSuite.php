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

namespace Extend\Warranty\ViewModel;

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class AjaxSuite
 */
class AjaxSuite implements ArgumentInterface
{
    /**
     * Data Helper
     * 
     * @var DataHelper 
     */
    private $dataHelper;

    /**
     * AjaxSuite constructor
     * 
     * @param DataHelper $dataHelper
     */
    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * Check if ajax suite enabled
     *
     * @return bool
     */
    public function isAjaxSuiteEnabled(): bool
    {
        return $this->dataHelper->isAjaxSuiteEnabled();
    }

    /**
     * Check if ajax suite cart enabled
     *
     * @return bool
     */
    public function isAjaxSuiteCartEnabled(): bool
    {
        return $this->dataHelper->isAjaxSuiteCartEnabled();
    }
}