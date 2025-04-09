<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Response;

class AnalyticsReport
{
    private array $dimensions = [];
    private array $metrics = [];
    private array $rows = [];
    private int $totalRows = 0;
    
    /**
     * @param array $dimensions List of dimension headers
     * @param array $metrics List of metric headers
     * @param array $rows Report data rows
     */
    public function __construct(array $dimensions, array $metrics, array $rows)
    {
        $this->dimensions = $dimensions;
        $this->metrics = $metrics;
        $this->rows = $rows;
        $this->totalRows = count($rows);
    }
    
    /**
     * Get the list of dimension headers.
     */
    public function getDimensions(): array
    {
        return $this->dimensions;
    }
    
    /**
     * Get the list of metric headers.
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }
    
    /**
     * Get the report rows.
     */
    public function getRows(): array
    {
        return $this->rows;
    }
    
    /**
     * Get the total number of rows.
     */
    public function getTotalRows(): int
    {
        return $this->totalRows;
    }
    
    /**
     * Get the rows as a simple array for easier handling.
     */
    public function toArray(): array
    {
        return $this->rows;
    }
    
    /**
     * Get a row by index.
     */
    public function getRow(int $index): ?array
    {
        return $this->rows[$index] ?? null;
    }
    
    /**
     * Get the first row or null if no rows.
     */
    public function getFirstRow(): ?array
    {
        return $this->totalRows > 0 ? $this->rows[0] : null;
    }
    
    /**
     * Check if the report has any rows.
     */
    public function hasRows(): bool
    {
        return $this->totalRows > 0;
    }
    
    /**
     * Get a single metric value from the first row or null if not found.
     */
    public function getMetricValue(string $metricName): mixed
    {
        $firstRow = $this->getFirstRow();
        return $firstRow[$metricName] ?? null;
    }
    
    /**
     * Get a single dimension value from the first row or null if not found.
     */
    public function getDimensionValue(string $dimensionName): ?string
    {
        $firstRow = $this->getFirstRow();
        return $firstRow[$dimensionName] ?? null;
    }
    
    /**
     * Extract a specific column from all rows.
     */
    public function getColumn(string $columnName): array
    {
        return array_column($this->rows, $columnName);
    }
}