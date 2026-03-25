<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\ClientBuilder;

/**
 * Provides batch size configuration for multi-read and multi-write operations.
 */
trait ManagesBatchingTrait
{
    private ?int $batchSize = null;

    /**
     * Set the batch size for multi-read and multi-write operations.
     *
     * @param int $batchSize Maximum items per batch (0 to disable batching).
     * @return self
     */
    public function setBatchSize(int $batchSize): self
    {
        $this->batchSize = $batchSize;

        return $this;
    }

    /**
     * Get the configured batch size, or null if not explicitly set.
     *
     * @return int|null
     */
    public function getBatchSize(): ?int
    {
        return $this->batchSize;
    }
}
