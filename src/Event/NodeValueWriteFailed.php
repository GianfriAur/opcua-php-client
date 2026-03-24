<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

/**
 * Dispatched when a write operation returns a non-Good status code.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesReadWriteTrait::write()
 */
readonly class NodeValueWriteFailed
{
    public function __construct(
        public OpcUaClientInterface $client,
        public NodeId $nodeId,
        public int $statusCode,
    ) {
    }
}
