<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched when all automatic retry attempts have been exhausted.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesConnectionTrait::executeWithRetry()
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
