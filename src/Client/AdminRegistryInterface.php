<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Client;

use Freema\GA4AnalyticsDataBundle\Admin\AdminClientInterface;
use Freema\GA4AnalyticsDataBundle\Exception\ClientConfigKeyDontExistException;

/**
 * Interface for GA4 Admin API client registry.
 */
interface AdminRegistryInterface
{
    /**
     * Add a client to the registry.
     *
     * @param string               $key    The client key
     * @param AdminClientInterface $client The client instance
     */
    public function addClient(string $key, AdminClientInterface $client): void;

    /**
     * Get a client by key.
     *
     * @param string $key The client key
     *
     * @throws ClientConfigKeyDontExistException If client key doesn't exist
     */
    public function getClient(string $key): AdminClientInterface;

    /**
     * Check if a client key exists.
     *
     * @param string $key The client key
     *
     * @return bool True if client exists
     */
    public function hasClient(string $key): bool;

    /**
     * Get all registered clients.
     *
     * @return array<string, AdminClientInterface> Associative array of clients
     */
    public function getClients(): array;
}
