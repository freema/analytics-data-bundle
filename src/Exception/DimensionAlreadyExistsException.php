<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Exception;

/**
 * Exception thrown when trying to create a dimension that already exists.
 */
class DimensionAlreadyExistsException extends AdminApiException
{
    public function __construct(
        private readonly string $parameterName,
        ?string $propertyId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Custom dimension with parameterName "%s" already exists', $parameterName),
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
