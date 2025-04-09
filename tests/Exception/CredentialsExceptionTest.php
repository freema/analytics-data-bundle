<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Tests\Exception;

use Freema\GA4AnalyticsDataBundle\Exception\CredentialsException;
use PHPUnit\Framework\TestCase;

class CredentialsExceptionTest extends TestCase
{
    public function testFileNotFound(): void
    {
        $path = '/path/to/credentials.json';
        $exception = CredentialsException::fileNotFound($path);

        $this->assertInstanceOf(CredentialsException::class, $exception);
        $this->assertStringContainsString('not found', $exception->getMessage());
        $this->assertStringContainsString($path, $exception->getMessage());
    }

    public function testFileNotReadable(): void
    {
        $path = '/path/to/credentials.json';
        $exception = CredentialsException::fileNotReadable($path);

        $this->assertInstanceOf(CredentialsException::class, $exception);
        $this->assertStringContainsString('not readable', $exception->getMessage());
        $this->assertStringContainsString($path, $exception->getMessage());
    }

    public function testFailedToRead(): void
    {
        $path = '/path/to/credentials.json';
        $exception = CredentialsException::failedToRead($path);

        $this->assertInstanceOf(CredentialsException::class, $exception);
        $this->assertStringContainsString('Failed to read', $exception->getMessage());
        $this->assertStringContainsString($path, $exception->getMessage());
    }

    public function testInvalidJson(): void
    {
        $errorMessage = 'Syntax error';
        $exception = CredentialsException::invalidJson($errorMessage);

        $this->assertInstanceOf(CredentialsException::class, $exception);
        $this->assertStringContainsString('invalid JSON', $exception->getMessage());
        $this->assertStringContainsString($errorMessage, $exception->getMessage());
    }

    public function testMissingRequiredField(): void
    {
        $field = 'project_id';
        $exception = CredentialsException::missingRequiredField($field);

        $this->assertInstanceOf(CredentialsException::class, $exception);
        $this->assertStringContainsString('missing required field', $exception->getMessage());
        $this->assertStringContainsString($field, $exception->getMessage());
    }

    public function testInvalidFormat(): void
    {
        $exception = CredentialsException::invalidFormat();

        $this->assertInstanceOf(CredentialsException::class, $exception);
        $this->assertStringContainsString('invalid format', $exception->getMessage());
    }
}
