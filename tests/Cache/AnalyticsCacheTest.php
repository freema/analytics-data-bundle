<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Tests\Cache;

use Freema\GA4AnalyticsDataBundle\Cache\AnalyticsCache;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class AnalyticsCacheTest extends TestCase
{
    public function testPropertiesSharingAPoolDoNotSeeEachOthersEntries(): void
    {
        // Every client is wired to cache.app, so the pool is shared
        $pool = new ArrayAdapter();
        $first = new AnalyticsCache($pool, 86400, true, '111111111');
        $second = new AnalyticsCache($pool, 86400, true, '222222222');

        $this->assertSame('first property', $first->get('total_visitors_pageviews_20260901_20260930', static fn () => 'first property'));
        $this->assertSame('second property', $second->get('total_visitors_pageviews_20260901_20260930', static fn () => 'second property'));
        $this->assertSame('first property', $first->get('total_visitors_pageviews_20260901_20260930', fn () => $this->fail('cache miss')));
    }

    public function testTheSamePropertySharesEntries(): void
    {
        $pool = new ArrayAdapter();
        (new AnalyticsCache($pool, namespace: '111111111'))->get('k', static fn () => 'cached');

        $this->assertSame('cached', (new AnalyticsCache($pool, namespace: '111111111'))->get('k', fn () => $this->fail('cache miss')));
    }

    public function testKeysUseTheConfiguredPrefix(): void
    {
        $pool = new ArrayAdapter();
        (new AnalyticsCache($pool, namespace: '111111111', prefix: 'my_app:ga4'))->get('k', static fn () => 'v');

        $keys = array_keys($pool->getValues());
        $this->assertNotEmpty($keys);
        foreach ($keys as $key) {
            // ":" is reserved in PSR-6 keys, so it is replaced
            $this->assertStringStartsWith('my_app_ga4.', (string) $key);
        }
    }

    public function testAnyParameterMakesAValidKey(): void
    {
        $cache = new AnalyticsCache(new ArrayAdapter(), namespace: '111111111');

        // "/" and ":" are reserved in PSR-6 keys; a transaction ID may contain them
        $this->assertTrue($cache->get('transaction_2026/10:001_20260401_20261001', static fn () => true));
    }

    public function testAFailureIsNotCached(): void
    {
        $cache = new AnalyticsCache(new ArrayAdapter(), namespace: '111111111');
        try {
            $cache->get('k', static fn () => throw new \RuntimeException('API down'));
            $this->fail('the exception was swallowed');
        } catch (\RuntimeException) {
        }

        $this->assertSame('fresh', $cache->get('k', static fn () => 'fresh'));
    }

    public function testDeleteDropsOneEntry(): void
    {
        $cache = new AnalyticsCache(new ArrayAdapter(), namespace: '111111111');
        $cache->get('k', static fn () => 'old');
        $cache->delete('k');

        $this->assertSame('new', $cache->get('k', static fn () => 'new'));
    }

    public function testClearDropsOnlyThisNamespace(): void
    {
        $pool = new ArrayAdapter();
        $unrelated = $pool->getItem('app.something_else');
        $pool->save($unrelated->set('keep me'));
        $first = new AnalyticsCache($pool, namespace: '111111111');
        $second = new AnalyticsCache($pool, namespace: '222222222');
        $first->get('k', static fn () => 'first');
        $second->get('k', static fn () => 'second');

        $this->assertTrue($first->clear());

        $this->assertSame('recomputed', $first->get('k', static fn () => 'recomputed'));
        $this->assertSame('second', $second->get('k', fn () => $this->fail('the other namespace was cleared')));
        $this->assertSame('keep me', $pool->getItem('app.something_else')->get());
    }

    public function testDisabledCacheAlwaysCallsTheCallback(): void
    {
        $cache = new AnalyticsCache(new ArrayAdapter(), enabled: false, namespace: '111111111');
        $calls = 0;
        $cache->get('k', function () use (&$calls) {
            return ++$calls;
        });
        $cache->get('k', function () use (&$calls) {
            return ++$calls;
        });

        $this->assertSame(2, $calls);
    }
}
