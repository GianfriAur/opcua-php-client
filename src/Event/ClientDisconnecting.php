<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched when the client begins disconnecting from the server.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesConnectionTrait::disconnect()
 */
readonly class ClientDisconnecting
{
    public function __construct(
        public OpcUaClientInterface $client,
        public ?string              $endpointUrl = null,
    ) {
    }
}
