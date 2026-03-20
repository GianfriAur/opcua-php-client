<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

class BrowsePathResult
{
    /**
     * @param int $statusCode
     * @param BrowsePathTarget[] $targets
     */
    public function __construct(
        public readonly int   $statusCode,
        public readonly array $targets,
    )
    {
    }
}
