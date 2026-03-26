<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched when the client begins a reconnection attempt.
 *
 * @see \PhpOpcua\Client\Client\ManagesConnectionTrait::reconnect()
 */
readonly class ClientReconnecting
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string $endpointUrl,
    ) {
    }
}
