<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;
use PhpOpcua\Client\Types\BuiltinType;
use PhpOpcua\Client\Types\NodeId;

/**
 * Dispatched after the client has successfully detected the write type for a node.
 *
 * Contains the detected BuiltinType and whether it was served from cache.
 *
 * @see \PhpOpcua\Client\Client\ManagesReadWriteTrait::resolveWriteType()
 */
readonly class WriteTypeDetected
{
    public function __construct(
        public OpcUaClientInterface $client,
        public NodeId $nodeId,
        public BuiltinType $detectedType,
        public bool $fromCache,
    ) {
    }
}
