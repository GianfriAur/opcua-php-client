<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Client;

use Gianfriaur\OpcuaPhpClient\Cache\InMemoryCache;
use Gianfriaur\OpcuaPhpClient\Event\CacheHit;
use Gianfriaur\OpcuaPhpClient\Event\CacheMiss;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Psr\SimpleCache\CacheInterface;

/**
 * Provides browse result caching using a PSR-16 cache backend.
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
     * Invalidate cached browse results for a specific node.
     *
     * @param NodeId|string $nodeId The node whose cache entries should be invalidated.
     * @return void
     */
    public function invalidateCache(NodeId|string $nodeId): void
    {
        $this->ensureCacheInitialized();
        if ($this->cache === null) {
            return;
        }

        $nodeId = $this->resolveNodeIdParam($nodeId);
        $prefix = $this->buildCacheKeyPrefix($nodeId);

        if ($this->cache instanceof InMemoryCache) {
            $this->invalidateByPrefix($prefix);

            return;
        }

        $this->cache->delete($prefix . ':browse');
        $this->cache->delete($prefix . ':browseAll');
        $this->cache->delete($prefix . ':writeType');

        foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 14, 15, 16, 17, 18, 19, 20, 21, 22, 26] as $attr) {
            $this->cache->delete($prefix . ':readMeta:' . $attr);
        }
    }

    /**
     * Flush the entire cache.
     *
     * @return void
     */
    public function flushCache(): void
    {
        $this->ensureCacheInitialized();
        if ($this->cache === null) {
            return;
        }
        $this->cache->clear();
    }

    /**
     * @param string $type
     * @param NodeId $nodeId
     * @param string $paramsSuffix
     * @return string
     */
    private function buildCacheKey(string $type, NodeId $nodeId, string $paramsSuffix = ''): string
    {
        $endpointHash = md5($this->lastEndpointUrl ?? 'unknown');
        $key = sprintf('opcua:%s:%s:%s', $endpointHash, $type, $nodeId->__toString());
        if ($paramsSuffix !== '') {
            $key .= ':' . $paramsSuffix;
        }

        return $key;
    }

    /**
     * @param NodeId $nodeId
     * @return string
     */
    private function buildCacheKeyPrefix(NodeId $nodeId): string
    {
        $endpointHash = md5($this->lastEndpointUrl ?? 'unknown');

        return sprintf('opcua:%s:%s', $endpointHash, $nodeId->__toString());
    }

    /**
     * @param string $type
     * @param string $paramsSuffix
     * @return string
     */
    private function buildSimpleCacheKey(string $type, string $paramsSuffix = ''): string
    {
        $endpointHash = md5($this->lastEndpointUrl ?? 'unknown');
        $key = sprintf('opcua:%s:%s', $endpointHash, $type);
        if ($paramsSuffix !== '') {
            $key .= ':' . $paramsSuffix;
        }

        return $key;
    }

    /**
     * @param string $key
     * @param callable $fetcher
     * @param bool $useCache
     * @return mixed
     */
    private function cachedFetch(string $key, callable $fetcher, bool $useCache): mixed
    {
        $this->ensureCacheInitialized();

        if ($useCache && $this->cache !== null) {
            $cached = $this->cache->get($key);
            if ($cached !== null) {
                $this->dispatch(fn () => new CacheHit($this, $key));

                return $cached;
            }
            $this->dispatch(fn () => new CacheMiss($this, $key));
        }

        $result = $fetcher();

        if ($useCache && $this->cache !== null) {
            $this->cache->set($key, $result);
        }

        return $result;
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

    /**
     * Deletes all InMemoryCache entries whose keys start with the given prefix.
     *
     * @param string $prefix
     * @return void
     */
    private function invalidateByPrefix(string $prefix): void
    {
        if ($this->cache instanceof InMemoryCache) {
            $this->cache->deleteByPrefix($prefix);
        }
    }
}
