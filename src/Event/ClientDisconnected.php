<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched after the client has fully disconnected from the server.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesConnectionTrait::disconnect()
 */
readonly class ClientDisconnected
{
    public function __construct(
        public OpcUaClientInterface $client,
    ) {
    }
}
