<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\DataCollector;

use Freema\GA4AnalyticsDataBundle\Client\AnalyticsRegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class AnalyticsDataCollector extends DataCollector
{
    private AnalyticsRegistryInterface $analyticsRegistry;

    public function __construct(AnalyticsRegistryInterface $analyticsRegistry)
    {
        $this->analyticsRegistry = $analyticsRegistry;
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $clients = $this->analyticsRegistry->getClients();
        $clientNames = array_keys($clients);

        // Gather client configurations
        $clientConfigs = [];
        foreach ($clients as $name => $client) {
            // Extract config via reflection to avoid adding a required method to the interface
            $reflectionClass = new \ReflectionClass($client);

            if ($reflectionClass->hasProperty('config')) {
                $configProperty = $reflectionClass->getProperty('config');
                $configProperty->setAccessible(true);
                $config = $configProperty->getValue($client);

                // Filter out sensitive data
                $filteredConfig = [
                    'property_id' => $config['property_id'] ?? null,
                    'cache_enabled' => isset($config['cache']) && ($config['cache_lifetime_in_minutes'] ?? 0) > 0,
                    'cache_lifetime' => $config['cache_lifetime_in_minutes'] ?? 0,
                    'proxy_enabled' => !empty($config['proxy']),
                ];

                $clientConfigs[$name] = $filteredConfig;
            } else {
                $clientConfigs[$name] = [
                    'info' => 'Configuration details not available',
                ];
            }
        }

        $this->data = [
            'client_names' => $clientNames,
            'client_count' => count($clientNames),
            'client_configs' => $clientConfigs,
        ];
    }

    public function getName(): string
    {
        return 'ga4_analytics_data.collector';
    }

    public function reset(): void
    {
        $this->data = [];
    }

    public function getClientNames(): array
    {
        return $this->data['client_names'] ?? [];
    }

    public function getClientCount(): int
    {
        return $this->data['client_count'] ?? 0;
    }

    public function getClientConfigs(): array
    {
        return $this->data['client_configs'] ?? [];
    }

    public function getConfig(string $clientName): array
    {
        return $this->data['client_configs'][$clientName] ?? [];
    }
}
