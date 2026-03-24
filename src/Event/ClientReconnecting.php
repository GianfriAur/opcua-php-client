<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched when the client begins a reconnection attempt.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesConnectionTrait::reconnect()
 */
readonly class ClientReconnecting
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string $endpointUrl,
    ) {
    }
}
