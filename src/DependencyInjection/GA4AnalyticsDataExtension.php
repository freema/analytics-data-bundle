<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\DependencyInjection;

use Freema\GA4AnalyticsDataBundle\Analytics\AnalyticsClient;
use Freema\GA4AnalyticsDataBundle\Analytics\AnalyticsClientInterface;
use Freema\GA4AnalyticsDataBundle\Cache\AnalyticsCache;
use Freema\GA4AnalyticsDataBundle\Client\AnalyticsRegistry;
use Freema\GA4AnalyticsDataBundle\DataCollector\AnalyticsDataCollector;
use Freema\GA4AnalyticsDataBundle\Http\GoogleAnalyticsClientFactory;
use Freema\GA4AnalyticsDataBundle\Http\HttpClientFactoryInterface;
use Freema\GA4AnalyticsDataBundle\Processor\ReportProcessor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class GA4AnalyticsDataExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $this->registerClients($config, $container);

        // Conditionally disable the data collector
        if (!$config['profiler']) {
            $container->removeDefinition(AnalyticsDataCollector::class);
        }
    }

    private function registerClients(array $config, ContainerBuilder $container): void
    {
        $registryDefinition = $container->getDefinition(AnalyticsRegistry::class);

        foreach ($config['clients'] as $name => $clientConfig) {
            // Create cache service for this client
            $cacheDefinition = new Definition(AnalyticsCache::class, [
                '$cache' => new Reference('cache.app'),
                '$lifetime' => ($clientConfig['cache']['lifetime_in_minutes'] ?? 1440) * 60, // Convert to seconds
                '$enabled' => $clientConfig['cache']['enabled'] ?? true,
            ]);
            $cacheServiceId = sprintf('ga4_analytics_data.cache.%s', $name);
            $container->setDefinition($cacheServiceId, $cacheDefinition);

            // Create processor instance
            $processorServiceId = sprintf('ga4_analytics_data.processor.%s', $name);
            $processorDefinition = new Definition(ReportProcessor::class);
            $container->setDefinition($processorServiceId, $processorDefinition);

            // Create Google Analytics client factory
            $factoryServiceId = sprintf('ga4_analytics_data.client_factory.%s', $name);
            $factoryDefinition = new Definition(GoogleAnalyticsClientFactory::class, [
                '$httpClientFactory' => new Reference(HttpClientFactoryInterface::class),
            ]);
            $container->setDefinition($factoryServiceId, $factoryDefinition);

            // Prepare client with configured cache
            $clientDefinition = new Definition(AnalyticsClient::class, [
                '$clientFactory' => new Reference($factoryServiceId),
                '$config' => $clientConfig,
                '$cache' => new Reference($cacheServiceId),
                '$processor' => new Reference($processorServiceId),
            ]);

            $clientDefinition->setPublic(true);
            $clientServiceId = sprintf('ga4_analytics_data.client.%s', $name);
            $container->setDefinition($clientServiceId, $clientDefinition);

            // Add client to registry
            $registryDefinition->addMethodCall('addClient', [$name, new Reference($clientServiceId)]);

            // Tag the client for autowiring
            $clientDefinition->addTag('ga4_analytics_data.client', ['key' => $name]);
        }

        // Configure default client if set
        if (isset($config['default_client'])) {
            $container->setParameter('ga4_analytics_data.default_client', $config['default_client']);

            // Set default client as the main AnalyticsClientInterface
            $defaultClientServiceId = sprintf('ga4_analytics_data.client.%s', $config['default_client']);
            $container->setAlias(AnalyticsClientInterface::class, $defaultClientServiceId);
        }
    }
}
