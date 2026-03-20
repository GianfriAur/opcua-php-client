<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Client;

/**
 * Provides automatic reconnection retry configuration for failed operations.
 */
trait ManagesAutoRetryTrait
{
    private ?int $autoRetry = null;

    /**
     * Set the maximum number of automatic reconnection retries on connection loss.
     *
     * @param int $maxRetries Maximum retry count (0 to disable).
     * @return self
     */
    public function setAutoRetry(int $maxRetries): self
    {
        $this->autoRetry = $maxRetries;

        return $this;
    }

    /**
     * Get the current automatic retry count.
     *
     * Returns the explicitly configured value, or 1 if a previous connection exists (enabling one automatic reconnect attempt), or 0 otherwise.
     *
     * @return int
     */
    public function getAutoRetry(): int
    {
        if ($this->autoRetry !== null) {
            return $this->autoRetry;
        }

        return $this->lastEndpointUrl !== null ? 1 : 0;
    }
}
