<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Exception;

use RuntimeException;

class ClientConfigKeyDontExistException extends RuntimeException
{
    public static function create(string $key): self
    {
        return new self(sprintf('Analytics client with key "%s" does not exist in the registry.', $key));
    }
}