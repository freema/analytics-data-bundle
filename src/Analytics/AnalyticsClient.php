<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Analytics;

use Freema\GA4AnalyticsDataBundle\Cache\AnalyticsCache;
use Freema\GA4AnalyticsDataBundle\Domain\OrderBy;
use Freema\GA4AnalyticsDataBundle\Domain\Period;
use Freema\GA4AnalyticsDataBundle\Exception\AnalyticsException;
use Freema\GA4AnalyticsDataBundle\Http\GoogleAnalyticsClientFactory;
use Freema\GA4AnalyticsDataBundle\Processor\ReportProcessor;
use Freema\GA4AnalyticsDataBundle\Response\AnalyticsReport;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\FilterExpressionList;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\RunReportResponse;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class AnalyticsClient implements AnalyticsClientInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ?BetaAnalyticsDataClient $analyticsClient = null;
    private array $config;
    private AnalyticsCache $cache;
    private ReportProcessor $processor;
    private GoogleAnalyticsClientFactory $clientFactory;

    public function __construct(
        GoogleAnalyticsClientFactory $clientFactory,
        array $config,
        AnalyticsCache $cache,
        ReportProcessor $processor,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->config = $config;
        $this->clientFactory = $clientFactory;
        $this->cache = $cache;
        $this->processor = $processor;
    }

    /**
     * Get the analytics client, creating it if it doesn't exist yet.
     */
    private function getAnalyticsClient(): BetaAnalyticsDataClient
    {
        if (null === $this->analyticsClient) {
            $this->analyticsClient = $this->clientFactory->createAnalyticsClient($this->config);
        }

        return $this->analyticsClient;
    }

    public function getMostViewedPages(?Period $period = null, int $limit = 20): array
    {
        if (!$period) {
            $period = Period::days(30);
        }

        $cacheKey = 'most_viewed_pages_'.$period->startDate->format('Ymd').'_'.$period->endDate->format('Ymd').'_'.$limit;

        $report = $this->cache->get($cacheKey, function () use ($period, $limit) {
            try {
                // Create the report request
                $request = $this->createBaseReportRequest();

                // Add dimensions
                $request->setDimensions([
                    new Dimension(['name' => 'pagePath']),
                    new Dimension(['name' => 'pageTitle']),
                ]);

                // Add metrics
                $request->setMetrics([
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'totalUsers']),
                    new Metric(['name' => 'userEngagementDuration']),
                ]);

                // Set date range
                $request->setDateRanges([$period->toDateRange()]);

                // Order by page views (descending)
                $orderBy = OrderBy::metric('screenPageViews', OrderBy::DESCENDING);
                $request->setOrderBys([$orderBy->toGoogleOrderBy()]);

                // Set limit
                $request->setLimit($limit);

                // Run the report
                $response = $this->getAnalyticsClient()->runReport($request);

                // Process the response
                return $this->processor->processMostViewedPagesReport($response);
            } catch (\Exception $e) {
                $this->logger->error('Failed to get most viewed pages', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw new AnalyticsException('Failed to get most viewed pages: '.$e->getMessage(), 0, $e);
            }
        });

        return $report->toArray();
    }

    public function isTransactionInAnalytics(string $transactionId, ?Period $period = null): bool
    {
        if (!$period) {
            // Default to 180 days for transaction lookup
            $period = Period::days(180);
        }

        $cacheKey = 'transaction_'.$transactionId.'_'.$period->startDate->format('Ymd').'_'.$period->endDate->format('Ymd');

        return $this->cache->get($cacheKey, function () use ($transactionId, $period) {
            try {
                $response = $this->runTransactionIdReport($transactionId, $period);

                // If we have any rows, order exists in GA
                return $response->getRowCount() > 0;
            } catch (\Exception $e) {
                $this->logger->error('GA transaction check failed', [
                    'transaction_id' => $transactionId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return false;
            }
        });
    }

    public function getTransactions(?Period $period = null): array
    {
        if (!$period) {
            $period = Period::days(30);
        }

        $cacheKey = 'transactions_'.$period->startDate->format('Ymd').'_'.$period->endDate->format('Ymd');

        $report = $this->cache->get($cacheKey, function () use ($period) {
            try {
                $response = $this->runTransactionsReport($period);

                return $this->processor->processTransactionsReport($response);
            } catch (\Exception $e) {
                $this->logger->error('GA transactions fetch failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw new AnalyticsException('Failed to fetch GA transactions: '.$e->getMessage(), 0, $e);
            }
        });

        return $report->toArray();
    }

    public function getVisitorsAndPageViews(?Period $period = null, ?string $dimension = 'date'): array
    {
        if (!$period) {
            $period = Period::days(30);
        }

        $cacheKey = 'visitors_pageviews_'.$period->startDate->format('Ymd').'_'.$period->endDate->format('Ymd').'_'.$dimension;

        $report = $this->cache->get($cacheKey, function () use ($period, $dimension) {
            try {
                // Create the report request
                $request = $this->createBaseReportRequest();

                // Add dimensions
                $request->setDimensions([
                    new Dimension(['name' => $dimension]),
                ]);

                // Add metrics
                $request->setMetrics([
                    new Metric(['name' => 'totalUsers']),
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'sessions']),
                ]);

                // Set date range
                $request->setDateRanges([$period->toDateRange()]);

                // Order by date if using date dimension
                if ('date' === $dimension) {
                    $orderBy = OrderBy::dimension('date', OrderBy::ASCENDING);
                    $request->setOrderBys([$orderBy->toGoogleOrderBy()]);
                }

                // Run the report
                $response = $this->getAnalyticsClient()->runReport($request);

                return $this->processor->processReport($response);
            } catch (\Exception $e) {
                $this->logger->error('Failed to get visitors and page views', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw new AnalyticsException('Failed to get visitors and page views: '.$e->getMessage(), 0, $e);
            }
        });

        return $report->toArray();
    }

    public function getTotalVisitorsAndPageViews(?Period $period = null): array
    {
        if (!$period) {
            $period = Period::days(30);
        }

        $cacheKey = 'total_visitors_pageviews_'.$period->startDate->format('Ymd').'_'.$period->endDate->format('Ymd');

        $report = $this->cache->get($cacheKey, function () use ($period) {
            try {
                // Create the report request
                $request = $this->createBaseReportRequest();

                // Add metrics
                $request->setMetrics([
                    new Metric(['name' => 'totalUsers']),
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'userEngagementDuration']),
                ]);

                // Set date range
                $request->setDateRanges([$period->toDateRange()]);

                // Run the report
                $response = $this->getAnalyticsClient()->runReport($request);

                $result = $this->processor->processReport($response);

                // Should be just one row with totals
                if (!$result->hasRows()) {
                    return new AnalyticsReport([], ['totalUsers', 'screenPageViews', 'sessions', 'userEngagementDuration'], [[
                        'totalUsers' => 0,
                        'screenPageViews' => 0,
                        'sessions' => 0,
                        'userEngagementDuration' => 0,
                    ]]);
                }

                return $result;
            } catch (\Exception $e) {
                $this->logger->error('Failed to get total visitors and page views', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw new AnalyticsException('Failed to get total visitors and page views: '.$e->getMessage(), 0, $e);
            }
        });

        // Return the first row or empty data
        return $report->getFirstRow() ?? [
            'totalUsers' => 0,
            'screenPageViews' => 0,
            'sessions' => 0,
            'userEngagementDuration' => 0,
        ];
    }

    public function getTopLandingPages(?Period $period = null, int $limit = 20): array
    {
        if (!$period) {
            $period = Period::days(30);
        }

        $cacheKey = 'top_landing_pages_'.$period->startDate->format('Ymd').'_'.$period->endDate->format('Ymd').'_'.$limit;

        $report = $this->cache->get($cacheKey, function () use ($period, $limit) {
            try {
                // Create the report request
                $request = $this->createBaseReportRequest();

                // Add dimensions
                $request->setDimensions([
                    new Dimension(['name' => 'landingPage']),
                    new Dimension(['name' => 'pageTitle']),
                ]);

                // Add metrics
                $request->setMetrics([
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'totalUsers']),
                    new Metric(['name' => 'bounceRate']),
                ]);

                // Set date range
                $request->setDateRanges([$period->toDateRange()]);

                // Order by sessions (descending)
                $orderBy = OrderBy::metric('sessions', OrderBy::DESCENDING);
                $request->setOrderBys([$orderBy->toGoogleOrderBy()]);

                // Set limit
                $request->setLimit($limit);

                // Run the report
                $response = $this->getAnalyticsClient()->runReport($request);

                return $this->processor->processReport($response);
            } catch (\Exception $e) {
                $this->logger->error('Failed to get top landing pages', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw new AnalyticsException('Failed to get top landing pages: '.$e->getMessage(), 0, $e);
            }
        });

        return $report->toArray();
    }

    public function getTopExitPages(?Period $period = null, int $limit = 20): array
    {
        if (!$period) {
            $period = Period::days(30);
        }

        $cacheKey = 'top_exit_pages_'.$period->startDate->format('Ymd').'_'.$period->endDate->format('Ymd').'_'.$limit;

        $report = $this->cache->get($cacheKey, function () use ($period, $limit) {
            try {
                // Create the report request
                $request = $this->createBaseReportRequest();

                // Add dimensions
                $request->setDimensions([
                    new Dimension(['name' => 'exitPage']),
                    new Dimension(['name' => 'pageTitle']),
                ]);

                // Add metrics
                $request->setMetrics([
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'exits']),
                    new Metric(['name' => 'exitRate']),
                ]);

                // Set date range
                $request->setDateRanges([$period->toDateRange()]);

                // Order by exits (descending)
                $orderBy = OrderBy::metric('exits', OrderBy::DESCENDING);
                $request->setOrderBys([$orderBy->toGoogleOrderBy()]);

                // Set limit
                $request->setLimit($limit);

                // Run the report
                $response = $this->getAnalyticsClient()->runReport($request);

                return $this->processor->processReport($response);
            } catch (\Exception $e) {
                $this->logger->error('Failed to get top exit pages', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw new AnalyticsException('Failed to get top exit pages: '.$e->getMessage(), 0, $e);
            }
        });

        return $report->toArray();
    }

    public function runReport(array $dimensions, array $metrics, ?Period $period = null, array $options = []): array
    {
        if (!$period) {
            $period = Period::days(30);
        }

        // Create a unique cache key based on all parameters
        $cacheKey = 'custom_report_'.md5(json_encode([
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'period' => [
                'start' => $period->startDate->format('Ymd'),
                'end' => $period->endDate->format('Ymd'),
            ],
            'options' => $options,
        ]));

        $report = $this->cache->get($cacheKey, function () use ($dimensions, $metrics, $period, $options) {
            try {
                // Create the report request
                $request = $this->createBaseReportRequest();

                // Add dimensions
                $googleDimensions = [];
                foreach ($dimensions as $dimension) {
                    $googleDimensions[] = new Dimension(['name' => $dimension]);
                }
                $request->setDimensions($googleDimensions);

                // Add metrics
                $googleMetrics = [];
                foreach ($metrics as $metric) {
                    $googleMetrics[] = new Metric(['name' => $metric]);
                }
                $request->setMetrics($googleMetrics);

                // Set date range
                $request->setDateRanges([$period->toDateRange()]);

                // Add filters if provided
                if (isset($options['dimensionFilter'])) {
                    $request->setDimensionFilter($this->createFilterExpression($options['dimensionFilter']));
                }

                if (isset($options['metricFilter'])) {
                    $request->setMetricFilter($this->createFilterExpression($options['metricFilter']));
                }

                // Add sorting if provided
                if (isset($options['orderBy'])) {
                    $googleOrderBys = [];
                    foreach ($options['orderBy'] as $orderBy) {
                        $googleOrderBys[] = $orderBy->toGoogleOrderBy();
                    }
                    $request->setOrderBys($googleOrderBys);
                }

                // Set limit if provided
                if (isset($options['limit'])) {
                    $request->setLimit($options['limit']);
                }

                // Set offset if provided
                if (isset($options['offset'])) {
                    $request->setOffset($options['offset']);
                }

                // Run the report
                $response = $this->getAnalyticsClient()->runReport($request);

                return $this->processor->processReport($response);
            } catch (\Exception $e) {
                $this->logger->error('Failed to run custom report', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'dimensions' => $dimensions,
                    'metrics' => $metrics,
                ]);

                throw new AnalyticsException('Failed to run custom report: '.$e->getMessage(), 0, $e);
            }
        });

        return $report->toArray();
    }

    /**
     * Run a GA report for a specific transaction ID.
     */
    private function runTransactionIdReport(string $transactionId, Period $period): RunReportResponse
    {
        // Create filter for specific transaction ID
        $stringFilter = new StringFilter();
        $stringFilter->setMatchType(StringFilter\MatchType::EXACT);
        $stringFilter->setValue($transactionId);

        $filter = new Filter();
        $filter->setStringFilter($stringFilter);
        $filter->setFieldName('transactionId');

        $transactionIdFilterExpression = new FilterExpression();
        $transactionIdFilterExpression->setFilter($filter);

        // Create filter for "purchase" event type
        $purchaseStringFilter = new StringFilter();
        $purchaseStringFilter->setMatchType(StringFilter\MatchType::EXACT);
        $purchaseStringFilter->setValue('purchase');

        $purchaseFilter = new Filter();
        $purchaseFilter->setStringFilter($purchaseStringFilter);
        $purchaseFilter->setFieldName('eventName');

        $purchaseFilterExpression = new FilterExpression();
        $purchaseFilterExpression->setFilter($purchaseFilter);

        $filterExpressionList = new FilterExpressionList();
        $filterExpressionList->setExpressions([
            $transactionIdFilterExpression,
            $purchaseFilterExpression,
        ]);

        $andFilterExpression = new FilterExpression();
        $andFilterExpression->setAndGroup($filterExpressionList);

        // Create the report request
        $request = $this->createBaseReportRequest();

        // Add dimensions for GA4
        $request->setDimensions([
            new Dimension(['name' => 'eventName']),
            new Dimension(['name' => 'dateHour']),
            new Dimension(['name' => 'transactionId']),
        ]);

        // Add metrics for GA4
        $request->setMetrics([
            new Metric(['name' => 'eventCount']),
            new Metric(['name' => 'totalRevenue']),
        ]);

        // Set date range
        $request->setDateRanges([$period->toDateRange()]);

        // Add filter
        $request->setDimensionFilter($andFilterExpression);

        // Run the report
        return $this->getAnalyticsClient()->runReport($request);
    }

    /**
     * Run a GA report for all transactions in a date range.
     */
    private function runTransactionsReport(Period $period): RunReportResponse
    {
        // Create the report request
        $request = $this->createBaseReportRequest();

        // Add dimensions for GA4
        $request->setDimensions([
            new Dimension(['name' => 'eventName']),
            new Dimension(['name' => 'dateHour']),
            new Dimension(['name' => 'transactionId']),
        ]);

        // Add metrics for GA4
        $request->setMetrics([
            new Metric(['name' => 'eventCount']),
            new Metric(['name' => 'totalRevenue']),
        ]);

        // Set date range
        $request->setDateRanges([$period->toDateRange()]);

        // Filter for "purchase" events
        $stringFilter = new StringFilter();
        $stringFilter->setMatchType(StringFilter\MatchType::EXACT);
        $stringFilter->setValue('purchase');

        $filter = new Filter();
        $filter->setStringFilter($stringFilter);
        $filter->setFieldName('eventName');

        $filterExpression = new FilterExpression();
        $filterExpression->setFilter($filter);
        $request->setDimensionFilter($filterExpression);

        // Run the report
        return $this->getAnalyticsClient()->runReport($request);
    }

    /**
     * Create a base report request with the property ID set.
     */
    private function createBaseReportRequest(): RunReportRequest
    {
        $request = new RunReportRequest();
        $request->setProperty('properties/'.$this->config['property_id']);

        return $request;
    }

    /**
     * Create a filter expression from an array definition.
     */
    private function createFilterExpression(array $filterDef): FilterExpression
    {
        $filterExpression = new FilterExpression();

        if (isset($filterDef['and'])) {
            $expressionList = new FilterExpressionList();
            $expressions = [];

            foreach ($filterDef['and'] as $subFilter) {
                $expressions[] = $this->createFilterExpression($subFilter);
            }

            $expressionList->setExpressions($expressions);
            $filterExpression->setAndGroup($expressionList);
        } elseif (isset($filterDef['or'])) {
            $expressionList = new FilterExpressionList();
            $expressions = [];

            foreach ($filterDef['or'] as $subFilter) {
                $expressions[] = $this->createFilterExpression($subFilter);
            }

            $expressionList->setExpressions($expressions);
            $filterExpression->setOrGroup($expressionList);
        } elseif (isset($filterDef['not'])) {
            $filterExpression->setNotExpression($this->createFilterExpression($filterDef['not']));
        } else {
            // Leaf filter
            $filter = new Filter();

            if (isset($filterDef['stringFilter'])) {
                $stringFilter = new StringFilter();
                $stringFilter->setValue($filterDef['stringFilter']['value']);

                // Set match type (default to EXACT)
                $matchType = StringFilter\MatchType::EXACT;
                if (isset($filterDef['stringFilter']['matchType'])) {
                    $matchType = $filterDef['stringFilter']['matchType'];
                }
                $stringFilter->setMatchType($matchType);

                // Set case sensitivity (default to false)
                if (isset($filterDef['stringFilter']['caseSensitive'])) {
                    $stringFilter->setCaseSensitive($filterDef['stringFilter']['caseSensitive']);
                } else {
                    $stringFilter->setCaseSensitive(false);
                }

                $filter->setStringFilter($stringFilter);
            }
            // Add other filter types here as needed

            $filter->setFieldName($filterDef['fieldName']);
            $filterExpression->setFilter($filter);
        }

        return $filterExpression;
    }
}
