<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\ClientBuilder;

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
     * @return int
     */
    public function getAutoRetry(): int
    {
        return $this->autoRetry ?? 0;
    }
}
