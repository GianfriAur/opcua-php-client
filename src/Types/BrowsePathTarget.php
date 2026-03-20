<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

readonly class BrowsePathTarget
{
    public function __construct(
        public NodeId $targetId,
        public int    $remainingPathIndex,
    )
    {
    }
}
