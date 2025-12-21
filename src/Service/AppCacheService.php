<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class AppCacheService
{
    public function __construct(
        private CacheInterface         $cache,
        private CacheItemPoolInterface $cachePool
    ) {
    }

    /**
     * Get cached data or compute and cache it if not found
     */
    public function getOrCompute(string $key, callable $computation, int $ttl = 900): mixed
    {
        return $this->cache->get($key, function() use ($computation) {
            return $computation();
        }, $ttl);
    }

    /**
     * Invalidate cache for a specific key
     */
    public function invalidate(string $key): void
    {
        $this->cache->delete($key);
    }

    /**
     * Clear all application cache
     */
    public function clearAll(): void
    {
        $this->cachePool->clear();
    }

    /**
     * Get cache item
     */
    public function get(string $key): mixed
    {
        $item = $this->cachePool->getItem($key);
        return $item->isHit() ? $item->get() : null;
    }

    /**
     * Set cache item
     */
    public function set(string $key, mixed $value, int $ttl = 900): void
    {
        $item = $this->cachePool->getItem($key);
        $item->set($value);
        $item->expiresAfter($ttl);
        $this->cachePool->save($item);
    }
}
