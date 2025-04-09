<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Tests\Analytics;

use Freema\GA4AnalyticsDataBundle\Analytics\AnalyticsClient;
use Freema\GA4AnalyticsDataBundle\Cache\AnalyticsCache;
use Freema\GA4AnalyticsDataBundle\Domain\Period;
use Freema\GA4AnalyticsDataBundle\Http\HttpClientFactoryInterface;
use Freema\GA4AnalyticsDataBundle\Processor\ReportProcessor;
use Freema\GA4AnalyticsDataBundle\Response\AnalyticsReport;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\RunReportResponse;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\NullLogger;

class AnalyticsClientTest extends TestCase
{
    private $httpClientFactoryMock;
    private $googleClientMock;
    private $cachePoolMock;
    private $processorMock;

    protected function setUp(): void
    {
        $this->googleClientMock = $this->createMock(BetaAnalyticsDataClient::class);

        $this->httpClientFactoryMock = $this->createMock(HttpClientFactoryInterface::class);
        $this->httpClientFactoryMock
            ->method('createClient')
            ->willReturn($this->googleClientMock);

        $this->cachePoolMock = $this->createMock(CacheItemPoolInterface::class);
        $this->processorMock = $this->createMock(ReportProcessor::class);
    }

    public function testGetMostViewedPages(): void
    {
        // Create mock response
        $response = $this->createMock(RunReportResponse::class);

        // Create mock report
        $report = new AnalyticsReport(
            ['pagePath', 'pageTitle'],
            ['screenPageViews', 'totalUsers', 'userEngagementDuration', 'avgEngagementSeconds'],
            [
                [
                    'pagePath' => '/home',
                    'pageTitle' => 'Home Page',
                    'screenPageViews' => 1000,
                    'totalUsers' => 500,
                    'userEngagementDuration' => 7500,
                    'avgEngagementSeconds' => 15.0,
                ],
            ]
        );

        // Configure the processor mock to return our report
        $this->processorMock
            ->method('processMostViewedPagesReport')
            ->willReturn($report);

        // Configure Google client to return our mock response
        $this->googleClientMock
            ->method('runReport')
            ->willReturn($response);

        // Mock cache to bypass caching
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->method('set')->willReturnSelf();
        $cacheItem->method('expiresAfter')->willReturnSelf();

        $this->cachePoolMock
            ->method('getItem')
            ->willReturn($cacheItem);

        $cache = new AnalyticsCache($this->cachePoolMock, 60, true);

        // Create analytics client with test configuration
        $config = [
            'property_id' => '123456789',
            'service_account_credentials_json' => __DIR__.'/../fixtures/credentials.json',
        ];

        $client = new AnalyticsClient(
            $this->httpClientFactoryMock,
            $config,
            $cache,
            $this->processorMock,
            new NullLogger()
        );

        // Run test
        $result = $client->getMostViewedPages(Period::days(7), 10);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('/home', $result[0]['pagePath']);
        $this->assertEquals('Home Page', $result[0]['pageTitle']);
        $this->assertEquals(1000, $result[0]['screenPageViews']);
        $this->assertEquals(500, $result[0]['totalUsers']);
        $this->assertEquals(7500, $result[0]['userEngagementDuration']);
        $this->assertEquals(15.0, $result[0]['avgEngagementSeconds']);
    }
}
