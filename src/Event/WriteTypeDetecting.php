<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;
use PhpOpcua\Client\Types\NodeId;

/**
 * Dispatched when the client begins detecting the write type for a node.
 *
 * This event fires before the cache lookup or server read used to determine
 * the node's BuiltinType for a write operation.
 *
 * @see \PhpOpcua\Client\Client\ManagesReadWriteTrait::resolveWriteType()
 */
readonly class WriteTypeDetecting
{
    public function __construct(
        public OpcUaClientInterface $client,
        public NodeId $nodeId,
    ) {
    }
}
