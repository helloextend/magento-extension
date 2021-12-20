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

namespace Extend\Warranty\Observer\Warranty;

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Normalizer;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Class Normalize
 */
class Normalize implements ObserverInterface
{
    /**
     * Normalizer
     *
     * @var Normalizer
     */
    private $normalizer;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Checkout Session
     *
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * LoggerInterface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Normalize constructor
     *
     * @param Normalizer $normalizer
     * @param DataHelper $dataHelper
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        Normalizer $normalizer,
        DataHelper $dataHelper,
        CheckoutSession $checkoutSession,
        LoggerInterface $logger
    ) {
        $this->normalizer = $normalizer;
        $this->dataHelper = $dataHelper;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * Normalize on cart|quote update
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        if (!$this->dataHelper->isBalancedCart()) {
            return;
        }

        try {
            $cart = $observer->getData('cart');
            $quote = !empty($cart) ? $cart->getQuote() : $this->checkoutSession->getQuote();
            $this->normalizer->normalize($quote);
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
