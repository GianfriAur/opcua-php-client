<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched when a connection attempt to an OPC UA server fails.
 *
 * @see \PhpOpcua\Client\Client\ManagesConnectionTrait::connect()
 */
readonly class ConnectionFailed
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string $endpointUrl,
        public \Throwable $exception,
    ) {
    }
}
