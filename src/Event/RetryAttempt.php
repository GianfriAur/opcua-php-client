<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched before each automatic retry attempt after a connection loss.
 *
 * @see \PhpOpcua\Client\Client\ManagesConnectionTrait::executeWithRetry()
 */
readonly class RetryAttempt
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int $attempt,
        public int $maxRetries,
        public \Throwable $exception,
    ) {
    }
}
