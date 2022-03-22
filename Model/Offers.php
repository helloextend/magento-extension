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

namespace Extend\Warranty\Model;

use Extend\Warranty\Helper\Api\Data as DataHelper;
use Extend\Warranty\Model\Api\Sync\Offer\OffersRequest as ApiOfferModel;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use InvalidArgumentException;

/**
 * Class Offers
 */
class Offers
{
    /**
     * Api Offer Model
     *
     * @var ApiOfferModel
     */
    private $apiOfferModel;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Json Serializer
     *
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * Logger Interface
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Offers constructor
     *
     * @param ApiOfferModel $apiOfferModel
     * @param DataHelper $dataHelper
     * @param JsonSerializer $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        ApiOfferModel $apiOfferModel,
        DataHelper $dataHelper,
        JsonSerializer $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->apiOfferModel = $apiOfferModel;
        $this->dataHelper = $dataHelper;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * Get offers
     *
     * @param string $productSku
     * @return array
     */
    public function getOffers(string $productSku): array
    {
        $apiUrl = $this->dataHelper->getApiUrl();
        $apiStoreId = $this->dataHelper->getStoreId();
        $apiKey = $this->dataHelper->getApiKey();

        try {
            $this->apiOfferModel->setConfig($apiUrl, $apiStoreId, $apiKey);
            $offers = $this->apiOfferModel->getOfferInformation($productSku);
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
            $offers = [];
        }

        return isset($offers['plans']) && is_array($offers['plans']) ? $offers['plans'] : [];
    }

    /**
     * Check if product has offers
     *
     * @param string $productSku
     * @return bool
     */
    public function hasOffers(string $productSku): bool
    {
        $offerInformation = $this->getOffers($productSku);
        $recommended = $offerInformation['recommended'] ?? '';

        return $recommended
            && isset($offerInformation[$recommended])
            && is_array($offerInformation[$recommended])
            && !empty($offerInformation[$recommended]);
    }

    /**
     * Get offers for order item
     *
     * @param OrderItemInterface $item
     * @return array
     */
    public function getOffersForOrderItem(OrderItemInterface $item): array
    {
        $storeId = $item->getStoreId();

        $apiUrl = $this->dataHelper->getApiUrl(ScopeInterface::SCOPE_STORES, $storeId);
        $apiStoreId = $this->dataHelper->getStoreId(ScopeInterface::SCOPE_STORES, $storeId);
        $apiKey = $this->dataHelper->getApiKey(ScopeInterface::SCOPE_STORES, $storeId);

        try {
            $this->apiOfferModel->setConfig($apiUrl, $apiStoreId, $apiKey);
            $productSku = $item->getSku();
            $offers = $this->apiOfferModel->getOfferInformation($productSku);
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
            $offers = [];
        }

        return isset($offers['plans']) && is_array($offers['plans']) ? $offers['plans'] : [];
    }

    /**
     * Check if order item has offers
     *
     * @param OrderItemInterface $item
     * @return bool
     */
    public function orderItemHasOffers(OrderItemInterface $item): bool
    {
        $offerInformation = $this->getOffersForOrderItem($item);
        $recommended = $offerInformation['recommended'] ?? '';

        return $recommended
            && isset($offerInformation[$recommended])
            && is_array($offerInformation[$recommended])
            && !empty($offerInformation[$recommended]);
    }

    /**
     * Validate warranty data
     *
     * @param array $warrantyData
     * @return array
     */
    public function validateWarranty(array $warrantyData): array
    {
        $errors = [];

        if (empty($warrantyData['planId'])) {
            $errors[] = __('Plan ID doesn\'t set.');
        }

        if (!isset($warrantyData['price'])) {
            $errors[] = __('Warranty plan price doesn\'t set.');
        } elseif ((int)$warrantyData['price'] <= 0) {
            $errors[] = __('Warranty plan price must be positive.');
        }

        if (empty($warrantyData['term'])) {
            $errors[] = __('Warranty term doesn\'t set.');
        }

        if (empty($warrantyData['product'])) {
            $errors[] = __('Product reference ID doesn\'t set.');
        }

        if (empty($errors)) {
            $offerInformation = $this->getOffers($warrantyData['product']);
            $recommended = $offerInformation['recommended'] ?? '';
            if ($recommended && isset($offerInformation[$recommended])) {
                $offerInfo = $offerInformation[$recommended];
                if (is_array($offerInfo) && !empty($offerInfo)) {
                    $offerIds = array_column($offerInfo, 'id');
                    if (in_array($warrantyData['planId'], $offerIds)) {
                        foreach ($offerInfo as $offer) {
                            if ($warrantyData['planId'] === $offer['id']) {
                                if (isset($offer['price']) && (int)$warrantyData['price'] !== $offer['price']) {
                                    $errors[] = __('Invalid price.');
                                }

                                if (
                                    isset($offer['contract']['termLength'])
                                    && (int)$warrantyData['term'] !== $offer['contract']['termLength']
                                ) {
                                    $errors[] = __('Invalid warranty term.');
                                }

                                break;
                            }
                        }
                    } else {
                        $errors[] = __('Invalid warranty plan ID.');
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Get warranty data as string
     *
     * @param array $warrantyData
     * @return string
     */
    public function getWarrantyDataAsString(array $warrantyData): string
    {
        try {
            $result = $this->jsonSerializer->serialize($warrantyData);
        } catch (InvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage());
            $result = '';
        }

        return $result;
    }
}
