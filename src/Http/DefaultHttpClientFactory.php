<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Http;

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use GuzzleHttp\Utils;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DefaultHttpClientFactory implements HttpClientFactoryInterface
{
    private LoggerInterface $logger;
    
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }
    
    public function createClient(array $config): BetaAnalyticsDataClient
    {
        $credentialsPath = $config['service_account_credentials_json'];
        
        // Handle relative paths
        if (!str_starts_with($credentialsPath, '/')) {
            $credentialsPath = getcwd() . '/' . $credentialsPath;
        }
        
        $clientConfig = [
            'credentials' => $credentialsPath,
        ];
        
        // Configure proxy if provided
        if (!empty($config['proxy'])) {
            $clientConfig['transportConfig'] = [
                'rest' => [
                    'httpHandler' => function ($request, $options) use ($config) {
                        $options['proxy'] = $config['proxy'];
                        
                        if (!empty($config['no_proxy'])) {
                            $options['no_proxy'] = implode(',', $config['no_proxy']);
                        }
                        
                        // Try to use the newer Utils::chooseHandler method if available,
                        // otherwise fall back to the deprecated choose_handler function
                        if (method_exists(Utils::class, 'chooseHandler')) {
                            $handler = Utils::chooseHandler();
                        } else {
                            $handler = \GuzzleHttp\choose_handler();
                        }
                        
                        return $handler($request, $options);
                    },
                ],
            ];
            
            $this->logger->info('Google Analytics API using proxy', [
                'proxy' => $config['proxy'],
                'no_proxy' => $config['no_proxy'] ?? [],
            ]);
        }
        
        return new BetaAnalyticsDataClient($clientConfig);
    }
}