<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Http;

use Freema\GA4AnalyticsDataBundle\Exception\AnalyticsException;
use Freema\GA4AnalyticsDataBundle\Exception\CredentialsException;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Factory for creating Google Analytics Data API clients.
 */
class GoogleAnalyticsClientFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private HttpClientFactoryInterface $httpClientFactory;

    public function __construct(
        HttpClientFactoryInterface $httpClientFactory,
        ?LoggerInterface $logger = null,
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Creates a Google Analytics Data API client.
     */
    public function createAnalyticsClient(array $config): BetaAnalyticsDataClient
    {
        try {
            // Validate config has required keys
            if (!isset($config['service_account_credentials_json'])) {
                throw new \InvalidArgumentException('Missing required configuration key: service_account_credentials_json');
            }

            // Create the client
            return $this->httpClientFactory->createClient($config);

        } catch (CredentialsException $e) {
            // Already a well-formatted credential exception, just log and rethrow
            $this->logger->error('Google Analytics credentials error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;

        } catch (\InvalidArgumentException $e) {
            // Missing config or other validation error
            $this->logger->error('Invalid Google Analytics configuration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;

        } catch (\Exception $e) {
            // Generic exception from Google API client or elsewhere
            $this->logger->error('Failed to create Google Analytics client', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Convert to our application-specific exception
            throw new AnalyticsException('Failed to initialize Google Analytics client: '.$e->getMessage(), 0, $e);
        }
    }
}
