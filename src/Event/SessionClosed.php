<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched after the OPC UA session has been closed.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSessionTrait::closeSession()
 */
readonly class SessionClosed
{
    public function __construct(
        public OpcUaClientInterface $client,
    ) {
    }
}
