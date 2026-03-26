<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;
use PhpOpcua\Client\Types\BrowseDirection;
use PhpOpcua\Client\Types\NodeId;

/**
 * Dispatched after a browse operation completes on a node.
 *
 * @see \PhpOpcua\Client\Client\ManagesBrowseTrait::browse()
 */
readonly class NodeBrowsed
{
    public function __construct(
        public OpcUaClientInterface $client,
        public NodeId $nodeId,
        public BrowseDirection $direction,
        public int $resultCount,
    ) {
    }
}
