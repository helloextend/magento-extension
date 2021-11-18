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

namespace Extend\Warranty\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Extend\Warranty\Model\Leads as LeadModel;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;

/**
 * Class Api
 */
class Api extends AbstractHelper
{
    /**
     * Lead Model
     *
     * @var LeadModel
     */
    private $leadModel;

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
     * Api constructor
     *
     * @param Context $context
     * @param LeadModel $leadModel
     * @param JsonSerializer $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        LeadModel $leadModel,
        JsonSerializer $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->leadModel = $leadModel;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Check if product has warranty offers
     *
     * @param string $productSku
     * @return bool
     */
    public function isProductHasOffers(string $productSku): bool
    {
        return $this->leadModel->hasOffers($productSku);
    }

    /**
     * Get offer information
     *
     * @param string $productSku
     * @return array
     */
    public function getOfferInformation(string $productSku): array
    {
        return $this->leadModel->getOffers($productSku);
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
            $offerInformation = $this->getOfferInformation($warrantyData['product']);
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
