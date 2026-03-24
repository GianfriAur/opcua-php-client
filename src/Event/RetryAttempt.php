<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched before each automatic retry attempt after a connection loss.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesConnectionTrait::executeWithRetry()
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
