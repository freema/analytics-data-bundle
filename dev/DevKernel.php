<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Dev;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class DevKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Symfony\Bundle\WebProfilerBundle\WebProfilerBundle(),
            new \Symfony\Bundle\DebugBundle\DebugBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Freema\GA4AnalyticsDataBundle\GA4AnalyticsDataBundle(),
        ];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/services.yaml');
        
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'router' => [
                'utf8' => true,
            ],
            'profiler' => [
                'only_exceptions' => false,
            ],
        ]);
        
        $container->extension('web_profiler', [
            'toolbar' => true,
            'intercept_redirects' => false,
        ]);
        
        $container->extension('twig', [
            'default_path' => '%kernel.project_dir%/templates',
            'debug' => true,
            'strict_variables' => true,
        ]);

        $container->extension('ga4_analytics_data', [
            'clients' => [
                'default' => [
                    'property_id' => '%env(ANALYTICS_PROPERTY_ID)%',
                    'service_account_credentials_json' => '%env(ANALYTICS_CREDENTIALS_PATH)%',
                    'cache_lifetime_in_minutes' => 1440,
                ],
            ],
            'default_client' => 'default',
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/routes.yaml');
        
        $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.xml')
            ->prefix('/_wdt');
        $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.xml')
            ->prefix('/_profiler');
    }
}