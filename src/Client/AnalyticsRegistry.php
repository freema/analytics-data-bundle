<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Client;

use Freema\GA4AnalyticsDataBundle\Analytics\AnalyticsClientInterface;
use Freema\GA4AnalyticsDataBundle\Exception\ClientConfigKeyDontExistException;

class AnalyticsRegistry implements AnalyticsRegistryInterface
{
    /**
     * @var array<string, AnalyticsClientInterface>
     */
    private array $clients = [];
    
    public function addClient(string $key, AnalyticsClientInterface $client): void
    {
        $this->clients[$key] = $client;
    }
    
    public function getClient(string $key): AnalyticsClientInterface
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