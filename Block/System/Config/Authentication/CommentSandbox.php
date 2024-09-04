<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Block\System\Config\Authentication;

use Exception;
use Extend\Warranty\Block\System\Config\AbstractSyncButton;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Button
 *
 * Renders Button Field
 */
class CommentSandbox extends AbstractSyncButton
{
    /**
     * Path to template file in theme
     *
     * @var string
     */
    protected $_template = "Extend_Warranty::system/config/authentication/commentsandbox.phtml";

    /**
     * Get Access Token Age
     *
     * @return string
     * @throws Exception
     */
    public function getTokenAgeSandbox(): string
    {
        $storeIds = $this->getScopeStoreIds();
        $scopeData = $this->getScopeData();
        $tokenAge = '';

        $note = "<br/><br/><em><strong>Note:</strong> The sandbox Api Key expires after 3 hours, and is refreshed automatically when called, or when you save the credentials in the authentication tab.</em>";

        $tokenAge = $this->dataHelper->getTokenAgeSandbox(ScopeInterface::SCOPE_STORES, $scopeData['scopeId']);
        if (!empty($tokenAge)) {
            $tokenAge = time() - $tokenAge;
            $expired = ($tokenAge > 10800) ? ' (expired)': '';

            $days = floor($tokenAge / (24 * 3600));
            $daysWord = $days > 1 ? ' days' : ' day';
            $tokenAge %= 24 * 3600;
            $hours = floor($tokenAge / 3600);
            $hoursWord = $hours > 1 ? ' hours' : ' hour';
            $tokenAge %= 3600;
            $minutes = floor($tokenAge / 60);
            $minutesWord = $minutes > 1 ? ' minutes' : ' minute';
            $seconds = $tokenAge % 60;
            $secondsWord = $seconds > 1 ? ' seconds' : ' second';

            $tokenAge = $days.$daysWord.', ' .$hours.$hoursWord.', ' . $minutes.$minutesWord. ', '.$seconds.$secondsWord.$expired.$note;

        }else{
            $tokenAge = 'Sandbox Api Key age will be shown after saving credentials first.';
        }

        return $tokenAge;
    }

}
