<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched when a connection attempt to an OPC UA server fails.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesConnectionTrait::connect()
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
