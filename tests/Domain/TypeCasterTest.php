<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Tests\Domain;

use Freema\GA4AnalyticsDataBundle\Domain\TypeCaster;
use PHPUnit\Framework\TestCase;

class TypeCasterTest extends TestCase
{
    /**
     * @dataProvider provideTypeCases
     */
    public function testCastValue(string $value, mixed $type, mixed $expected): void
    {
        $this->assertSame($expected, TypeCaster::castValue($value, $type));
    }

    public function provideTypeCases(): array
    {
        return [
            'string value' => ['test', 'STRING', 'test'],
            'integer value' => ['123', 'INTEGER', 123],
            'float value' => ['123.45', 'FLOAT', 123.45],
            'percent value' => ['75.5', 'PERCENT', 75.5],
            'time value' => ['60.5', 'TIME', 60.5],
            'currency value' => ['99.99', 'CURRENCY', 99.99],
            'metric currency value' => ['$99.99', 'METRIC_CURRENCY', 99.99],
            'metric currency with commas' => ['$1,099.99', 'METRIC_CURRENCY', 1099.99],
            'default for unknown type' => ['test', 'UNKNOWN_TYPE', 'test'],
        ];
    }
}
