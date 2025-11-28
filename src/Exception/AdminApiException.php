<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Exception;

/**
 * Exception thrown when a GA4 Admin API call fails.
 */
class AdminApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?string $propertyId = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getPropertyId(): ?string
    {
        return $this->propertyId;
    }
}
