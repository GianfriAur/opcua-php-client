<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

/**
 * Dispatched after a new OPC UA session has been created on the server.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSessionTrait::createAndActivateSession()
 */
readonly class SessionCreated
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string               $endpointUrl,
        public NodeId               $authenticationToken,
    ) {
    }
}
