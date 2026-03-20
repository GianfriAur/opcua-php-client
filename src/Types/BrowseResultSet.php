<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

readonly class BrowseResultSet
{
    /**
     * @param ReferenceDescription[] $references
     * @param ?string $continuationPoint
     */
    public function __construct(
        public array   $references,
        public ?string $continuationPoint,
    )
    {
    }
}
