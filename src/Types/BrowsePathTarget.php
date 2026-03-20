<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

class BrowsePathTarget
{
    public function __construct(
        public readonly NodeId $targetId,
        public readonly int    $remainingPathIndex,
    )
    {
    }
}
