<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Client;

use Freema\GA4AnalyticsDataBundle\Admin\AdminClientInterface;
use Freema\GA4AnalyticsDataBundle\Exception\ClientConfigKeyDontExistException;

/**
 * Registry for GA4 Admin API clients.
 */
class AdminRegistry implements AdminRegistryInterface
{
    /**
     * @var array<string, AdminClientInterface>
     */
    private array $clients = [];

    public function addClient(string $key, AdminClientInterface $client): void
    {
        $this->clients[$key] = $client;
    }

    public function getClient(string $key): AdminClientInterface
    {
        if (!$this->hasClient($key)) {
            throw ClientConfigKeyDontExistException::create($key);
        }

        return $this->clients[$key];
    }

    public function hasClient(string $key): bool
    {
        return isset($this->clients[$key]);
    }

    public function getClients(): array
    {
        return $this->clients;
    }
}
