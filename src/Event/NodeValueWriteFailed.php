<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;
use PhpOpcua\Client\Types\NodeId;

/**
 * Dispatched when a write operation returns a non-Good status code.
 *
 * @see \PhpOpcua\Client\Client\ManagesReadWriteTrait::write()
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
