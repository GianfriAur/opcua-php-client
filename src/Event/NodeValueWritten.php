<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Types\BuiltinType;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

/**
 * Dispatched after a successful write operation on a node.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesReadWriteTrait::write()
 */
readonly class NodeValueWritten
{
    public function __construct(
        public OpcUaClientInterface $client,
        public NodeId $nodeId,
        public mixed $value,
        public BuiltinType $type,
        public int $statusCode,
    ) {
    }
}
