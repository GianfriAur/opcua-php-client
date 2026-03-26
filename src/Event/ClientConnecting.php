<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched when the client begins connecting to an OPC UA server.
 *
 * @see \PhpOpcua\Client\Client\ManagesConnectionTrait::connect()
 */
readonly class ClientConnecting
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string $endpointUrl,
    ) {
    }
}
