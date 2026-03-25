<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Types\BuiltinType;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

/**
 * Dispatched after the client has successfully detected the write type for a node.
 *
 * Contains the detected BuiltinType and whether it was served from cache.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesReadWriteTrait::resolveWriteType()
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
