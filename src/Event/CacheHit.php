<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched when a cache lookup finds a cached result.
 *
 * @see \PhpOpcua\Client\Client\ManagesCacheTrait::cachedFetch()
 */
readonly class CacheHit
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string $key,
    ) {
    }
}
