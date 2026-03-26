<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched after the OPC UA session has been closed.
 *
 * @see \PhpOpcua\Client\Client\ManagesSessionTrait::closeSession()
 */
readonly class SessionClosed
{
    public function __construct(
        public OpcUaClientInterface $client,
    ) {
    }
}
