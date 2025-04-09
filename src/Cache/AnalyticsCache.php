<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Cache;

use Psr\Cache\CacheItemPoolInterface;

class AnalyticsCache
{
    private CacheItemPoolInterface $cache;
    private int $lifetime;
    private bool $enabled;

    public function __construct(
        CacheItemPoolInterface $cache,
        int $lifetime = 86400, // Default to 24 hours
        bool $enabled = true,
    ) {
        $this->cache = $cache;
        $this->lifetime = $lifetime;
        $this->enabled = $enabled;
    }

    /**
     * Get item from cache or compute it with the callback.
     */
    public function get(string $key, callable $callback)
    {
        // If caching is disabled, just call the callback directly
        if (!$this->enabled) {
            return $callback();
        }

        $item = $this->cache->getItem($key);

        if ($item->isHit()) {
            return $item->get();
        }

        $value = $callback();

        $item->set($value);
        $item->expiresAfter($this->lifetime);

        $this->cache->save($item);

        return $value;
    }

    /**
     * Clear a specific cache key.
     */
    public function delete(string $key): bool
    {
        return $this->cache->deleteItem($key);
    }

    /**
     * Clear all analytics cache.
     */
    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Enable or disable caching.
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Check if caching is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Set cache lifetime.
     */
    public function setLifetime(int $lifetime): void
    {
        $this->lifetime = $lifetime;
    }

    /**
     * Get current cache lifetime.
     */
    public function getLifetime(): int
    {
        return $this->lifetime;
    }
}
