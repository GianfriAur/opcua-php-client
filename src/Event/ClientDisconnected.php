<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched after the client has fully disconnected from the server.
 *
 * @see \PhpOpcua\Client\Client\ManagesConnectionTrait::disconnect()
 */
readonly class ClientDisconnected
{
    public function __construct(
        public OpcUaClientInterface $client,
    ) {
    }
}
