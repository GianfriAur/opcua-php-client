<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

/**
 * Holds the result of a TranslateBrowsePathsToNodeIds operation for a single browse path.
 *
 * @see \Gianfriaur\OpcuaPhpClient\OpcuaClient::translateBrowsePaths()
 */
readonly class BrowsePathResult
{
    /**
     * @param int $statusCode
     * @param BrowsePathTarget[] $targets
     */
    public function __construct(
        public int $statusCode,
        public array $targets,
    ) {
    }
}
