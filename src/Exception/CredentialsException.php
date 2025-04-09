<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Exception;

/**
 * Exception thrown when there are issues with Google Analytics credentials.
 */
class CredentialsException extends \InvalidArgumentException
{
    public static function fileNotFound(string $path): self
    {
        return new self(sprintf('Google Analytics credentials file not found at path: %s', $path));
    }
    
    public static function fileNotReadable(string $path): self
    {
        return new self(sprintf('Google Analytics credentials file is not readable at path: %s', $path));
    }
    
    public static function failedToRead(string $path): self
    {
        return new self(sprintf('Failed to read Google Analytics credentials file at path: %s', $path));
    }
    
    public static function invalidJson(string $errorMessage): self
    {
        return new self(sprintf('Google Analytics credentials file contains invalid JSON: %s', $errorMessage));
    }
    
    public static function missingRequiredField(string $field): self
    {
        return new self(sprintf('Google Analytics credentials file is missing required field: %s', $field));
    }
    
    public static function invalidFormat(): self
    {
        return new self('Google Analytics credentials file has an invalid format. Please ensure you are using a service account credentials JSON file.');
    }
}