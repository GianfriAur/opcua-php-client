<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched when all automatic retry attempts have been exhausted.
 *
 * @see \PhpOpcua\Client\Client\ManagesConnectionTrait::executeWithRetry()
 */
readonly class RetryExhausted
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int $attempts,
        public \Throwable $exception,
    ) {
    }
}
