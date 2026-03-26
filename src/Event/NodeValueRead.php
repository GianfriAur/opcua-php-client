<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;
use PhpOpcua\Client\Types\DataValue;
use PhpOpcua\Client\Types\NodeId;

/**
 * Dispatched after a single node attribute has been read.
 *
 * @see \PhpOpcua\Client\Client\ManagesReadWriteTrait::read()
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
