<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Http;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;

interface HttpClientFactoryInterface
{
    /**
     * Create a new Google Analytics Data API client.
     *
     * @param array $config Client configuration
     * @return BetaAnalyticsDataClient
     */
    public function createClient(array $config): BetaAnalyticsDataClient;
}