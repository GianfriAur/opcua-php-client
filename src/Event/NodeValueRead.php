<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Types\DataValue;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

/**
 * Dispatched after a single node attribute has been read.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesReadWriteTrait::read()
 */
readonly class NodeValueRead
{
    public function __construct(
        public OpcUaClientInterface $client,
        public NodeId $nodeId,
        public int $attributeId,
        public DataValue $dataValue,
    ) {
    }
}
