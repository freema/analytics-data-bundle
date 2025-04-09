# Google Analytics 4 Data API Bundle for Symfony

A Symfony bundle for working with the Google Analytics 4 Data API. This bundle supports multiple Google Analytics properties, cache configuration, and provides a clean API to query analytics data.

## Installation

Install the bundle using Composer:

```bash
composer require freema/ga4-analytics-data-bundle
```

## Requirements

- PHP 8.1+
- Symfony 5.4|6.4|7.1
- Google Analytics 4 Property
- Service Account credentials with access to the GA4 property

## Configuration

Register the bundle in your `config/bundles.php`:

```php
return [
    // ...
    Freema\GA4AnalyticsDataBundle\GA4AnalyticsDataBundle::class => ['all' => true],
];
```

Create the configuration file at `config/packages/ga4_analytics_data.yaml`:

```yaml
ga4_analytics_data:
    clients:
        default:
            property_id: '%env(ANALYTICS_PROPERTY_ID)%'
            service_account_credentials_json: '%env(ANALYTICS_CREDENTIALS_PATH)%'
            cache:
                enabled: true                   # Enable/disable caching
                lifetime_in_minutes: 1440       # 24 hours cache lifetime
                prefix: 'ga4_analytics_data'    # Cache key prefix
            # Optional proxy configuration
            proxy: '%env(default::ANALYTICS_PROXY)%' 
            no_proxy: []
        # You can define multiple clients with different properties
        another_property:
            property_id: '%env(ANOTHER_ANALYTICS_PROPERTY_ID)%'
            service_account_credentials_json: '%env(ANOTHER_ANALYTICS_CREDENTIALS_PATH)%'
    # The default client to use when none is specified
    default_client: 'default'
    # Enable/disable the Symfony profiler integration
    profiler: true
```

Make sure to add the required environment variables or replace with actual values:

```
ANALYTICS_PROPERTY_ID=123456789
ANALYTICS_CREDENTIALS_PATH=/path/to/credentials.json
```

## Usage

### Basic Usage

```php
use Freema\GA4AnalyticsDataBundle\Analytics\AnalyticsClientInterface;
use Freema\GA4AnalyticsDataBundle\Domain\Period;

class ReportingController
{
    public function dashboard(AnalyticsClientInterface $analyticsClient)
    {
        // Get statistics for the last 30 days
        $period = Period::days(30);
        
        // Get most viewed pages
        $topPages = $analyticsClient->getMostViewedPages($period, 10);
        
        // Get visitors and pageviews by date
        $visitorsData = $analyticsClient->getVisitorsAndPageViews($period);
        
        // Get total visitors and pageviews
        $totals = $analyticsClient->getTotalVisitorsAndPageViews($period);
        
        return $this->render('dashboard.html.twig', [
            'topPages' => $topPages,
            'visitorsData' => $visitorsData,
            'totals' => $totals,
        ]);
    }
}
```

### Using Multiple Clients

```php
use Freema\GA4AnalyticsDataBundle\Client\AnalyticsRegistryInterface;

class MultiPropertyReportingController
{
    private $analyticsRegistry;
    
    public function __construct(AnalyticsRegistryInterface $analyticsRegistry)
    {
        $this->analyticsRegistry = $analyticsRegistry;
    }
    
    public function compareProperties()
    {
        $period = Period::days(30);
        
        // Get data from first property
        $defaultClient = $this->analyticsRegistry->getClient('default');
        $defaultData = $defaultClient->getTotalVisitorsAndPageViews($period);
        
        // Get data from second property
        $anotherClient = $this->analyticsRegistry->getClient('another_property');
        $anotherData = $anotherClient->getTotalVisitorsAndPageViews($period);
        
        return $this->render('compare.html.twig', [
            'defaultData' => $defaultData,
            'anotherData' => $anotherData,
        ]);
    }
}
```

### Custom Queries

```php
// Run a custom report
$results = $analyticsClient->runReport(
    // Dimensions
    ['date', 'deviceCategory', 'browser'],
    // Metrics 
    ['totalUsers', 'newUsers', 'sessions'],
    // Period
    Period::months(3),
    // Options
    [
        'orderBy' => [
            OrderBy::dimension('date', OrderBy::ASCENDING),
        ],
        'limit' => 100,
    ]
);
```

### Period Helper

The bundle includes a `Period` class to easily define date ranges:

```php
// Last 30 days
$period = Period::days(30);

// Last 6 months
$period = Period::months(6);

// Last year
$period = Period::years(1);

// Custom period
$period = Period::create(
    new \DateTime('2023-01-01'),
    new \DateTime('2023-12-31')
);
```

### Caching

The bundle provides flexible caching options:

- **Enable/Disable**: Set `enabled: true/false` in the cache config section
- **Lifetime**: Configure with `lifetime_in_minutes` (defaults to 1440 = 24 hours)
- **Custom Cache**: The bundle uses Symfony's cache system, so you can configure any PSR-6 cache adapter

### Symfony Web Profiler Integration

The bundle includes a data collector for the Symfony Web Profiler that shows:

- Configured Analytics clients
- Property IDs
- Cache settings
- Proxy configuration

This can be disabled by setting `profiler: false` in the bundle config.

## Getting Google Analytics 4 Credentials

1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the "Google Analytics Data API" for your project
4. Go to "APIs & Services" > "Credentials"
5. Create a new "Service Account"
6. Download the JSON key file for the service account
7. In Google Analytics, go to Admin > Property > Property Access Management
8. Add the service account email with "Viewer" permissions

## License

This bundle is released under the MIT License. See the included LICENSE file.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for information on how to contribute to this project.