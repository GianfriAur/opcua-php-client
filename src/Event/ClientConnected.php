<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched after the client has successfully connected to an OPC UA server.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesConnectionTrait::connect()
 */
readonly class ClientConnected
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string $endpointUrl,
    ) {
    }
}
