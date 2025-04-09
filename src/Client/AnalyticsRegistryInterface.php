<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Client;

use Freema\GA4AnalyticsDataBundle\Analytics\AnalyticsClientInterface;
use Freema\GA4AnalyticsDataBundle\Exception\ClientConfigKeyDontExistException;

interface AnalyticsRegistryInterface
{
    /**
     * Add a client to the registry.
     *
     * @param string $key The client key
     * @param AnalyticsClientInterface $client The client instance
     * @return void
     */
    public function addClient(string $key, AnalyticsClientInterface $client): void;
    
    /**
     * Get a client by key.
     *
     * @param string $key The client key
     * @return AnalyticsClientInterface
     * @throws ClientConfigKeyDontExistException If client key doesn't exist
     */
    public function getClient(string $key): AnalyticsClientInterface;
    
    /**
     * Check if a client key exists.
     *
     * @param string $key The client key
     * @return bool True if client exists
     */
    public function hasClient(string $key): bool;
    
    /**
     * Get all registered clients.
     *
     * @return array<string, AnalyticsClientInterface> Associative array of clients
     */
    public function getClients(): array;
}