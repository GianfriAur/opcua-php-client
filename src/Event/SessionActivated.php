<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched after the OPC UA session has been activated with credentials.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSessionTrait::createAndActivateSession()
 */
readonly class SessionActivated
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string $endpointUrl,
    ) {
    }
}
