<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched when a cache lookup does not find a cached result.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesCacheTrait::cachedFetch()
 */
readonly class CacheMiss
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string               $key,
    ) {
    }
}
