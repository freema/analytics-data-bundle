<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Exception;

/**
 * Exception thrown when a dimension is not found.
 */
class DimensionNotFoundException extends AdminApiException
{
    public function __construct(
        private readonly string $parameterName,
        ?string $propertyId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Custom dimension with parameterName "%s" not found', $parameterName),
            $propertyId,
            0,
            $previous
        );
    }

    public function getParameterName(): string
    {
        return $this->parameterName;
    }
}
