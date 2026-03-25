<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\ClientBuilder;

use Gianfriaur\OpcuaPhpClient\Cache\InMemoryCache;
use Psr\SimpleCache\CacheInterface;

/**
 * Provides cache configuration using a PSR-16 cache backend.
 */
trait ManagesCacheTrait
{
    private ?CacheInterface $cache = null;

    private bool $cacheInitialized = false;

    /**
     * Set the cache driver. Pass null to disable caching entirely.
     *
     * @param ?CacheInterface $cache A PSR-16 cache instance, or null to disable.
     * @return self
     */
    public function setCache(?CacheInterface $cache): self
    {
        $this->cache = $cache;
        $this->cacheInitialized = true;

        return $this;
    }

    /**
     * Get the current cache driver, or null if caching is disabled.
     *
     * @return ?CacheInterface
     */
    public function getCache(): ?CacheInterface
    {
        $this->ensureCacheInitialized();

        return $this->cache;
    }

    /**
     * Initializes the cache with a default InMemoryCache if not yet configured.
     *
     * @return void
     */
    private function ensureCacheInitialized(): void
    {
        if (! $this->cacheInitialized) {
            $this->cache = new InMemoryCache(300);
            $this->cacheInitialized = true;
        }
    }
}
