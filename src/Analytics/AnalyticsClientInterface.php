<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Analytics;

use Freema\GA4AnalyticsDataBundle\Domain\Period;

interface AnalyticsClientInterface
{
    /**
     * Get top most viewed pages in the given time period.
     *
     * @param Period|null $period Time period for the report (defaults to last 30 days)
     * @param int         $limit  Number of top pages to return (default 20)
     *
     * @return array List of pages with view stats
     */
    public function getMostViewedPages(?Period $period = null, int $limit = 20): array;

    /**
     * Check if a specific transaction exists in Google Analytics.
     *
     * @param string      $transactionId The transaction ID to look for
     * @param Period|null $period        Time period for the search (defaults to last 180 days)
     *
     * @return bool True if the transaction exists in GA
     */
    public function isTransactionInAnalytics(string $transactionId, ?Period $period = null): bool;

    /**
     * Get all transactions from GA for a given date range.
     *
     * @param Period|null $period Time period for the report (defaults to last 30 days)
     *
     * @return array List of transaction data from GA
     */
    public function getTransactions(?Period $period = null): array;

    /**
     * Get visitor and page view data for a specific period.
     *
     * @param Period|null $period    Time period for the report (defaults to last 30 days)
     * @param string|null $dimension Optional dimension to group by (e.g. 'date')
     *
     * @return array Visitor and page view data
     */
    public function getVisitorsAndPageViews(?Period $period = null, ?string $dimension = 'date'): array;

    /**
     * Get total visitors and page views for a specific period.
     *
     * @param Period|null $period Time period for the report (defaults to last 30 days)
     *
     * @return array Total visitor and page view counts
     */
    public function getTotalVisitorsAndPageViews(?Period $period = null): array;

    /**
     * Get the most popular landing pages.
     *
     * @param Period|null $period Time period for the report (defaults to last 30 days)
     * @param int         $limit  Number of landing pages to return (default 20)
     *
     * @return array List of landing pages with stats
     */
    public function getTopLandingPages(?Period $period = null, int $limit = 20): array;

    /**
     * Get the pages with highest exit rates.
     *
     * @param Period|null $period Time period for the report (defaults to last 30 days)
     * @param int         $limit  Number of exit pages to return (default 20)
     *
     * @return array List of exit pages with stats
     */
    public function getTopExitPages(?Period $period = null, int $limit = 20): array;

    /**
     * Execute a custom Google Analytics Data API report.
     *
     * @param array       $dimensions List of dimensions for the report
     * @param array       $metrics    List of metrics for the report
     * @param Period|null $period     Time period for the report (defaults to last 30 days)
     * @param array       $options    additional options like filters, orderBy, etc
     *
     * @return array Report results
     */
    public function runReport(array $dimensions, array $metrics, ?Period $period = null, array $options = []): array;
}
