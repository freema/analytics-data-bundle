<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Domain;

class TypeCaster
{
    /**
     * Cast a value to the appropriate type based on Google Analytics metric type.
     *
     * @param string           $value The value to cast
     * @param string|int|mixed $type  The Google Analytics metric type
     *
     * @return mixed The cast value
     */
    public static function castValue(string $value, mixed $type): mixed
    {
        // If the type is not a string (e.g., it's an integer enum value), convert to string
        $typeStr = is_string($type) ? $type : (string) $type;

        return match ($typeStr) {
            'INTEGER' => (int) $value,
            'FLOAT', 'PERCENT', 'TIME', 'CURRENCY' => (float) $value,
            // @phpstan-ignore-next-line
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
