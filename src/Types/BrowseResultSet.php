<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

/**
 * Holds the result of a browse operation, including references and an optional continuation point.
 *
 * @see \Gianfriaur\OpcuaPhpClient\OpcuaClient::browseWithContinuation()
 */
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
