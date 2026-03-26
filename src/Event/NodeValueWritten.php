<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;
use PhpOpcua\Client\Types\BuiltinType;
use PhpOpcua\Client\Types\NodeId;

/**
 * Dispatched after a successful write operation on a node.
 *
 * @see \PhpOpcua\Client\Client\ManagesReadWriteTrait::write()
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
