<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched when the client begins connecting to an OPC UA server.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesConnectionTrait::connect()
 */
readonly class ClientConnecting
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string               $endpointUrl,
    ) {
    }
}
