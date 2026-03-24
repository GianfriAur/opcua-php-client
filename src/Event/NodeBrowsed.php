<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Types\BrowseDirection;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

/**
 * Dispatched after a browse operation completes on a node.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesBrowseTrait::browse()
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
