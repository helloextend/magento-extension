<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Model\Product;

use Magento\Catalog\Api\Data\ProductTierPriceExtensionFactory;
use Magento\Catalog\Model\Product\Type\Price as AbstractPrice;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\CatalogRule\Model\ResourceModel\RuleFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Exception;

/**
 * Class Price
 */
class Price extends AbstractPrice
{

    /**
     * Json Serializer
     *
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * Price Constructor
     *
     * @param RuleFactory $ruleFactory
     * @param StoreManagerInterface $storeManager
     * @param TimezoneInterface $localeDate
     * @param Session $customerSession
     * @param ManagerInterface $eventManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param GroupManagementInterface $groupManagement
     * @param ProductTierPriceInterfaceFactory $tierPriceFactory
     * @param ScopeConfigInterface $config
     * @param JsonSerializer $jsonSerializer
     * @param ProductTierPriceExtensionFactory|null $tierPriceExtensionFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        RuleFactory $ruleFactory,
        StoreManagerInterface $storeManager,
        TimezoneInterface $localeDate,
        Session $customerSession,
        ManagerInterface $eventManager,
        PriceCurrencyInterface $priceCurrency,
        GroupManagementInterface $groupManagement,
        ProductTierPriceInterfaceFactory $tierPriceFactory,
        ScopeConfigInterface $config,
        JsonSerializer $jsonSerializer,
        ProductTierPriceExtensionFactory $tierPriceExtensionFactory = null
    ) {
        parent::__construct(
            $ruleFactory,
            $storeManager,
            $localeDate,
            $customerSession,
            $eventManager,
            $priceCurrency,
            $groupManagement,
            $tierPriceFactory,
            $config,
            $tierPriceExtensionFactory
        );
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @inheritDoc
     */
    public function getFinalPrice($qty, $product)
    {
        $buyRequest = $product->getCustomOption('info_buyRequest');
        if ($buyRequest && $buyRequest->getValue()) {
            $buyRequestJsonValue = $buyRequest->getValue();

            try {
                $buyRequestValue = $this->jsonSerializer->unserialize($buyRequestJsonValue);
            } catch (Exception $exception) {
                $buyRequestValue = [];
            }

            if (!empty($buyRequestValue) && isset($buyRequestValue['custom_price'])) {
                $finalPrice = (float)$buyRequestValue['custom_price'];
            }
        }

        return $finalPrice ?? parent::getFinalPrice($qty, $product);
    }
}
