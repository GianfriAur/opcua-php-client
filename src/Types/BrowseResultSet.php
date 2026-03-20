<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

class BrowseResultSet
{
    /**
     * @param ReferenceDescription[] $references
     * @param ?string $continuationPoint
     */
    public function __construct(
        public readonly array   $references,
        public readonly ?string $continuationPoint,
    )
    {
    }
}
