<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Client;

use Gianfriaur\OpcuaPhpClient\Client;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;
use Throwable;

/**
 * Provides batch size configuration and server operation limit discovery for multi-read and multi-write operations.
 */
trait ManagesBatchingTrait
{
    private ?int $batchSize = null;
    private ?int $serverMaxNodesPerRead = null;
    private ?int $serverMaxNodesPerWrite = null;

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

    /**
     * Get the server-reported maximum nodes per read operation, or null if unknown.
     *
     * @return int|null
     */
    public function getServerMaxNodesPerRead(): ?int
    {
        return $this->serverMaxNodesPerRead;
    }

    /**
     * Get the server-reported maximum nodes per write operation, or null if unknown.
     *
     * @return int|null
     */
    public function getServerMaxNodesPerWrite(): ?int
    {
        return $this->serverMaxNodesPerWrite;
    }

    /**
     * @return int|null
     */
    private function getEffectiveReadBatchSize(): ?int
    {
        if ($this->batchSize !== null) {
            return $this->batchSize > 0 ? $this->batchSize : null;
        }

        return $this->serverMaxNodesPerRead;
    }

    /**
     * @return int|null
     */
    private function getEffectiveWriteBatchSize(): ?int
    {
        if ($this->batchSize !== null) {
            return $this->batchSize > 0 ? $this->batchSize : null;
        }

        return $this->serverMaxNodesPerWrite;
    }

    private function discoverServerOperationLimits(): void
    {
        if ($this->batchSize === 0) {
            return;
        }

        $items = [
            ['nodeId' => NodeId::numeric(0, 11705)],
            ['nodeId' => NodeId::numeric(0, 11707)],
        ];

        try {
            $results = $this->readMultiRaw($items);

            if (isset($results[0]) && StatusCode::isGood($results[0]->getStatusCode())) {
                $value = $results[0]->getValue();
                if (is_int($value) && $value > 0) {
                    $this->serverMaxNodesPerRead = $value;
                }
            }

            if (isset($results[1]) && StatusCode::isGood($results[1]->getStatusCode())) {
                $value = $results[1]->getValue();
                if (is_int($value) && $value > 0) {
                    $this->serverMaxNodesPerWrite = $value;
                }
            }
        } catch (Throwable) {
            // Server doesn't support operation limits — not critical
        }
    }

    private function resetBatchingState(): void
    {
        $this->serverMaxNodesPerRead = null;
        $this->serverMaxNodesPerWrite = null;
    }
}
