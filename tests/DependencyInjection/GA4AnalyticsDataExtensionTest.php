<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Tests\DependencyInjection;

use Freema\GA4AnalyticsDataBundle\DependencyInjection\GA4AnalyticsDataExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class GA4AnalyticsDataExtensionTest extends TestCase
{
    public function testEachClientCachesUnderItsOwnPropertyAndPrefix(): void
    {
        $container = new ContainerBuilder();
        (new GA4AnalyticsDataExtension())->load([[
            'clients' => [
                'shop' => [
                    'property_id' => '111111111',
                    'service_account_credentials_json' => 'shop.json',
                ],
                'blog' => [
                    'property_id' => '222222222',
                    'service_account_credentials_json' => 'blog.json',
                    'cache' => ['prefix' => 'blog_ga4'],
                ],
            ],
        ]], $container);

        $shop = $container->getDefinition('ga4_analytics_data.cache.shop');
        $blog = $container->getDefinition('ga4_analytics_data.cache.blog');

        $this->assertSame('111111111', $shop->getArgument('$namespace'));
        $this->assertSame('ga4_analytics_data', $shop->getArgument('$prefix'));
        $this->assertSame('222222222', $blog->getArgument('$namespace'));
        $this->assertSame('blog_ga4', $blog->getArgument('$prefix'));
    }
}
