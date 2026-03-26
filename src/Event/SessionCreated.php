<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;
use PhpOpcua\Client\Types\NodeId;

/**
 * Dispatched after a new OPC UA session has been created on the server.
 *
 * @see \PhpOpcua\Client\Client\ManagesSessionTrait::createAndActivateSession()
 */
readonly class SessionCreated
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string $endpointUrl,
        public NodeId $authenticationToken,
    ) {
    }
}
