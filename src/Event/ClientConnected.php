<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched after the client has successfully connected to an OPC UA server.
 *
 * @see \PhpOpcua\Client\Client\ManagesConnectionTrait::connect()
 */
readonly class ClientConnected
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string $endpointUrl,
    ) {
    }
}
