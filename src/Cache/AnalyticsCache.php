<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Cache;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches API responses for one client in a pool it may share with others
 * (by default `cache.app`).
 *
 * Every key is scoped to a namespace, the client's property ID, so two
 * properties never read each other's entries, and is hashed, so any report
 * parameter (a transaction ID with "/" or ":", say) makes a valid PSR-6 key.
 * clear() drops this namespace only: it moves the namespace to a new
 * generation, and the old entries expire on their own.
 */
class AnalyticsCache
{
    private CacheItemPoolInterface $cache;
    private int $lifetime;
    private bool $enabled;
    private string $namespace;
    private string $prefix;

    public function __construct(
        CacheItemPoolInterface $cache,
        int $lifetime = 86400, // Default to 24 hours
        bool $enabled = true,
        string $namespace = '',
        string $prefix = 'ga4_analytics_data',
    ) {
        $this->cache = $cache;
        $this->lifetime = $lifetime;
        $this->enabled = $enabled;
        $this->namespace = $namespace;
        // PSR-6 only guarantees A-Z, a-z, 0-9, "_" and "." in keys
        $this->prefix = (string) preg_replace('/[^A-Za-z0-9_.]/', '_', $prefix);
    }

    /**
     * Get item from cache or compute it with the callback.
     *
     * Nothing is stored when the callback throws.
     */
    public function get(string $key, callable $callback): mixed
    {
        // If caching is disabled, just call the callback directly
        if (!$this->enabled) {
            return $callback();
        }

        $item = $this->cache->getItem($this->itemKey($key));

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
        return $this->cache->deleteItem($this->itemKey($key));
    }

    /**
     * Clear this client's cache. Other entries in the pool stay.
     */
    public function clear(): bool
    {
        $item = $this->cache->getItem($this->generationKey());
        $item->set($this->generation() + 1);

        return $this->cache->save($item);
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

    private function itemKey(string $key): string
    {
        return $this->hashedKey($this->generation()."\0".$key);
    }

    private function generationKey(): string
    {
        return $this->hashedKey('generation');
    }

    private function generation(): int
    {
        $item = $this->cache->getItem($this->generationKey());
        $generation = $item->isHit() ? $item->get() : 0;

        return is_int($generation) ? $generation : 0;
    }

    private function hashedKey(string $key): string
    {
        return $this->prefix.'.'.substr(hash('sha256', $this->namespace."\0".$key), 0, 32);
    }
}
