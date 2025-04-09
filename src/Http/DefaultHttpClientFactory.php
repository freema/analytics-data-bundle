<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Http;

use Freema\GA4AnalyticsDataBundle\Exception\CredentialsException;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
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
        
        // Validate credentials file exists and is readable
        if (!file_exists($credentialsPath)) {
            $this->logger->error('Google Analytics credentials file not found', [
                'path' => $credentialsPath,
            ]);
            throw CredentialsException::fileNotFound($credentialsPath);
        }
        
        if (!is_readable($credentialsPath)) {
            $this->logger->error('Google Analytics credentials file not readable', [
                'path' => $credentialsPath,
            ]);
            throw CredentialsException::fileNotReadable($credentialsPath);
        }
        
        // Check if file is valid JSON
        $fileContents = file_get_contents($credentialsPath);
        if ($fileContents === false) {
            $this->logger->error('Failed to read Google Analytics credentials file', [
                'path' => $credentialsPath,
            ]);
            throw CredentialsException::failedToRead($credentialsPath);
        }
        
        $json = json_decode($fileContents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Google Analytics credentials file contains invalid JSON', [
                'path' => $credentialsPath,
                'error' => json_last_error_msg(),
            ]);
            throw CredentialsException::invalidJson(json_last_error_msg());
        }
        
        // Check for required fields in the credentials file
        $requiredFields = ['type', 'project_id', 'private_key_id', 'private_key', 'client_email'];
        foreach ($requiredFields as $field) {
            if (!isset($json[$field])) {
                $this->logger->error('Google Analytics credentials file missing required field', [
                    'path' => $credentialsPath,
                    'missing_field' => $field,
                ]);
                throw CredentialsException::missingRequiredField($field);
            }
        }
        
        // Verify this is a service account credentials file
        if ($json['type'] !== 'service_account') {
            $this->logger->error('Google Analytics credentials file has invalid type', [
                'path' => $credentialsPath,
                'expected' => 'service_account',
                'actual' => $json['type'],
            ]);
            throw CredentialsException::invalidFormat();
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