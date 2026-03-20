<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

readonly class BrowsePathResult
{
    /**
     * @param int $statusCode
     * @param BrowsePathTarget[] $targets
     */
    public function __construct(
        public int   $statusCode,
        public array $targets,
    )
    {
    }
}
