<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Exception;

/**
 * Exception thrown when validation fails.
 */
class ValidationException extends \InvalidArgumentException
{
    /**
     * @param string[] $errors
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get individual validation errors.
     *
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
