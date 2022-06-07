<?php
/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */

namespace Extend\Warranty\Api;

/**
 * Interface SyncInterface
 */
interface SyncInterface
{
    /**
     * Default batch size
     */
    public const DEFAULT_BATCH_SIZE = 100;

    /**
     * Get items
     *
     * @param int $batchNumber
     * @param array $filters
     * @return array
     */
    public function getItems(int $batchNumber, array $filters = []): array;

    /**
     * Get batch size
     *
     * @return int
     */
    public function getBatchSize(): int;

    /**
     * Get count of batches to process
     *
     * @return int
     */
    public function getCountOfBatches(): int;

    /**
     * Set batch size
     *
     * @param int $batchSize
     */
    public function setBatchSize(int $batchSize);

    /**
     * Set count of batches to process
     *
     * @param int $countOfItems
     */
    public function setCountOfBatches(int $countOfItems);
}
