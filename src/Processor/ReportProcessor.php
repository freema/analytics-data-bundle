<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Processor;

use Freema\GA4AnalyticsDataBundle\Domain\TypeCaster;
use Freema\GA4AnalyticsDataBundle\Response\AnalyticsReport;
use Google\Analytics\Data\V1beta\RunReportResponse;

class ReportProcessor
{
    /**
     * Process a generic GA report response into a structured report object.
     */
    public function processReport(RunReportResponse $response): AnalyticsReport
    {
        $dimensionHeaders = $response->getDimensionHeaders();
        $metricHeaders = $response->getMetricHeaders();
        $rows = [];

        // Process each row
        foreach ($response->getRows() as $row) {
            $result = [];

            // Process dimensions
            foreach ($row->getDimensionValues() as $i => $dimensionValue) {
                $result[$dimensionHeaders[$i]->getName()] = $dimensionValue->getValue();
            }

            // Process metrics
            foreach ($row->getMetricValues() as $i => $metricValue) {
                $headerName = $metricHeaders[$i]->getName();
                $value = $metricValue->getValue();
                $type = $metricHeaders[$i]->getType();

                $result[$headerName] = TypeCaster::castValue($value, $type);
            }

            $rows[] = $result;
        }

        // Convert iterator to array properly
        $dimensionHeadersArray = iterator_to_array($dimensionHeaders->getIterator());
        $metricHeadersArray = iterator_to_array($metricHeaders->getIterator());

        $dimensions = array_map(fn ($header) => $header->getName(), $dimensionHeadersArray);
        $metrics = array_map(fn ($header) => $header->getName(), $metricHeadersArray);

        return new AnalyticsReport($dimensions, $metrics, $rows);
    }

    /**
     * Process the most viewed pages report with additional calculated metrics.
     */
    public function processMostViewedPagesReport(RunReportResponse $response): AnalyticsReport
    {
        $report = $this->processReport($response);
        $rows = $report->getRows();

        // Add calculated metrics
        foreach ($rows as &$row) {
            if (isset($row['userEngagementDuration']) && isset($row['totalUsers']) && (int) $row['totalUsers'] > 0) {
                $row['avgEngagementSeconds'] = round((float) $row['userEngagementDuration'] / (int) $row['totalUsers'], 2);
            } else {
                $row['avgEngagementSeconds'] = 0;
            }
        }

        $dimensions = $report->getDimensions();
        $metrics = array_merge($report->getMetrics(), ['avgEngagementSeconds']);

        return new AnalyticsReport($dimensions, $metrics, $rows);
    }

    /**
     * Process transactions report with standardized field names.
     */
    public function processTransactionsReport(RunReportResponse $response): AnalyticsReport
    {
        $report = $this->processReport($response);
        $rows = $report->getRows();

        // Standardize field names for transactions
        foreach ($rows as &$row) {
            // Map transaction ID fields
            foreach (['transactionId', 'purchaseTransactionId'] as $field) {
                if (isset($row[$field])) {
                    $row['transactionId'] = $row[$field];
                }
            }

            // Map revenue fields
            foreach (['totalRevenue', 'purchaseRevenue'] as $field) {
                if (isset($row[$field])) {
                    $row['transactionRevenue'] = $row[$field];
                }
            }
        }

        return new AnalyticsReport($report->getDimensions(), $report->getMetrics(), $rows);
    }
}
