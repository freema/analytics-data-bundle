<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Domain;

use Google\Analytics\Data\V1beta\OrderBy as GoogleOrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;

class OrderBy
{
    public const ASCENDING = 'asc';
    public const DESCENDING = 'desc';
    
    private string $name;
    private bool $isMetric;
    private string $direction;
    
    private function __construct(string $name, bool $isMetric, string $direction)
    {
        $this->name = $name;
        $this->isMetric = $isMetric;
        $this->direction = $direction;
    }
    
    public static function metric(string $metricName, string $direction = self::DESCENDING): self
    {
        return new self($metricName, true, $direction);
    }
    
    public static function dimension(string $dimensionName, string $direction = self::ASCENDING): self
    {
        return new self($dimensionName, false, $direction);
    }
    
    public function toGoogleOrderBy(): GoogleOrderBy
    {
        $orderBy = new GoogleOrderBy();
        
        if ($this->isMetric) {
            $metricOrderBy = new MetricOrderBy();
            $metricOrderBy->setMetricName($this->name);
            $orderBy->setMetric($metricOrderBy);
        } else {
            $dimensionOrderBy = new DimensionOrderBy();
            $dimensionOrderBy->setDimensionName($this->name);
            $orderBy->setDimension($dimensionOrderBy);
        }
        
        $orderBy->setDesc($this->direction === self::DESCENDING);
        
        return $orderBy;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function isMetric(): bool
    {
        return $this->isMetric;
    }
    
    public function getDirection(): string
    {
        return $this->direction;
    }
}