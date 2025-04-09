<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ga4_analytics_data');
        $rootNode = $treeBuilder->getRootNode();

        $this->addClientsSection($rootNode);

        return $treeBuilder;
    }

    private function addClientsSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('clients')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('property_id')
                                ->isRequired()
                                ->info('The property ID from Google Analytics 4')
                            ->end()
                            ->scalarNode('service_account_credentials_json')
                                ->isRequired()
                                ->info('Path to the service account credentials JSON file')
                            ->end()
                            ->arrayNode('cache')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('enabled')
                                        ->defaultTrue()
                                        ->info('Whether to enable caching of API responses')
                                    ->end()
                                    ->integerNode('lifetime_in_minutes')
                                        ->defaultValue(1440) // 24 hours
                                        ->info('The amount of minutes the Google API responses will be cached')
                                    ->end()
                                    ->scalarNode('prefix')
                                        ->defaultValue('ga4_analytics_data')
                                        ->info('Cache key prefix')
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('proxy')
                                ->defaultNull()
                                ->info('HTTP proxy URL if required')
                            ->end()
                            ->arrayNode('no_proxy')
                                ->scalarPrototype()->end()
                                ->info('List of domains to exclude from proxy')
                            ->end()
                        ->end()
                    ->end()
                    ->requiresAtLeastOneElement()
                    ->isRequired()
                ->end()
                ->scalarNode('default_client')
                    ->defaultNull()
                    ->info('Default client to use when none is specified')
                ->end()
                ->booleanNode('profiler')
                    ->defaultTrue()
                    ->info('Whether to enable the data collector for Symfony profiler')
                ->end()
            ->end()
        ;
    }
}