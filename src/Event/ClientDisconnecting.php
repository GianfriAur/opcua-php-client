<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched when the client begins disconnecting from the server.
 *
 * @see \PhpOpcua\Client\Client\ManagesConnectionTrait::disconnect()
 */
readonly class ClientDisconnecting
{
    public function __construct(
        public OpcUaClientInterface $client,
        public ?string $endpointUrl = null,
    ) {
    }
}
