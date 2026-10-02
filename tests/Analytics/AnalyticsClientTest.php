<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Tests\Analytics;

use Freema\GA4AnalyticsDataBundle\Analytics\AnalyticsClient;
use Freema\GA4AnalyticsDataBundle\Cache\AnalyticsCache;
use Freema\GA4AnalyticsDataBundle\Http\GoogleAnalyticsClientFactory;
use Freema\GA4AnalyticsDataBundle\Processor\ReportProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * The Google client is final, so these tests only cover paths that fail
 * before a report comes back.
 */
class AnalyticsClientTest extends TestCase
{
    public function testAFailedTransactionCheckIsNotCached(): void
    {
        $factory = $this->createMock(GoogleAnalyticsClientFactory::class);
        // Both calls reach the API: the first failure was not cached as "false"
        $factory->expects($this->exactly(2))
            ->method('createAnalyticsClient')
            ->willThrowException(new \RuntimeException('API unavailable'));

        $client = new AnalyticsClient(
            $factory,
            ['property_id' => '111111111'],
            new AnalyticsCache(new ArrayAdapter()),
            new ReportProcessor(),
        );

        $this->assertFalse($client->isTransactionInAnalytics('T-1001'));
        $this->assertFalse($client->isTransactionInAnalytics('T-1001'));
    }
}
