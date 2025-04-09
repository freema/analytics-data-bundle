<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Domain;

class TypeCaster
{
    public static function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'INTEGER' => (int) $value,
            'FLOAT', 'PERCENT', 'TIME', 'CURRENCY' => (float) $value,
            'METRIC_CURRENCY' => static::castMetricCurrency($value),
            default => $value,
        };
    }
    
    /**
     * Cast a currency metric to its numeric value.
     */
    private static function castMetricCurrency(string $value): float
    {
        // Strip any currency symbols and separators, then cast to float
        $value = preg_replace('/[^0-9.]/', '', $value);
        
        return (float) $value;
    }
}