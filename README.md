# Google Analytics 4 Data API Bundle for Symfony

A Symfony bundle for working with the Google Analytics 4 Data API. This bundle supports multiple Google Analytics properties, cache configuration, and provides a clean API to query analytics data.

## Installation

Install the bundle using Composer:

```bash
composer require freema/ga4-analytics-data-bundle
```

The bundle uses [Symfony Flex](https://symfony.com/doc/current/setup/flex.html), so it will automatically:
- Enable the bundle in `config/bundles.php` 
- Create the configuration file `config/packages/ga4_analytics_data.yaml`
- Add environment variables to your `.env` file
- Add the credentials file path to your `.gitignore`

### Manual Installation (Without Flex)

If you're not using Symfony Flex, you need to:

1. Register the bundle in your `config/bundles.php`:

```php
return [
    // ...
    Freema\GA4AnalyticsDataBundle\GA4AnalyticsDataBundle::class => ['all' => true],
];
```

2. Create the configuration file at `config/packages/ga4_analytics_data.yaml`:

```yaml
ga4_analytics_data:
    clients:
        default:
            property_id: '%env(ANALYTICS_PROPERTY_ID)%'
            service_account_credentials_json: '%env(ANALYTICS_CREDENTIALS_PATH)%'
            cache:
                enabled: true                   # Enable/disable caching
                lifetime_in_minutes: 1440       # 24 hours cache lifetime
            # Optional proxy configuration
            proxy: '%env(default::ANALYTICS_PROXY)%' 
            no_proxy: []
        # You can define multiple clients with different properties
        # another_property:
        #     property_id: '%env(ANOTHER_ANALYTICS_PROPERTY_ID)%'
        #     service_account_credentials_json: '%env(ANOTHER_ANALYTICS_CREDENTIALS_PATH)%'
    # The default client to use when none is specified
    default_client: 'default'
    # Enable/disable the Symfony profiler integration
    profiler: '%kernel.debug%'
```

3. Add the required environment variables to your `.env` file:

```
###> freema/ga4-analytics-data-bundle ###
ANALYTICS_PROPERTY_ID=123456789
ANALYTICS_CREDENTIALS_PATH=%kernel.project_dir%/config/analytics-credentials.json
# ANALYTICS_PROXY=http://proxy.example.com:8080
###< freema/ga4-analytics-data-bundle ###
```

## Requirements

- PHP 8.1+
- Symfony 5.4|6.4|7.1
- Google Analytics 4 Property
- Service Account credentials with access to the GA4 property

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

### Creating Service Account Credentials

1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the "Google Analytics Data API" for your project
4. Go to "APIs & Services" > "Credentials"
5. Create a new "Service Account"
6. Download the JSON key file for the service account
7. In Google Analytics, go to Admin > Property > Property Access Management
8. Add the service account email with "Viewer" permissions

### Credentials File Format

The service account credentials file should be a JSON file with the following structure:

```json
{
  "type": "service_account",
  "project_id": "your-project-id",
  "private_key_id": "key-id",
  "private_key": "-----BEGIN PRIVATE KEY-----\nPrivate key content\n-----END PRIVATE KEY-----\n",
  "client_email": "your-service-account@your-project.iam.gserviceaccount.com",
  "client_id": "client-id",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/your-service-account%40your-project.iam.gserviceaccount.com"
}
```

### Security Best Practices

1. **Store credentials securely**: Keep the JSON file in a secure location, outside of your web root
2. **Use environment variables**: Reference the path to the credentials file using environment variables
3. **Restrict permissions**: Set appropriate file permissions to limit access to the credentials file
4. **Environment-specific paths**: Use different paths for different environments (dev, staging, prod)

```yaml
# config/packages/ga4_analytics_data.yaml
ga4_analytics_data:
    clients:
        default:
            service_account_credentials_json: '%env(ANALYTICS_CREDENTIALS_PATH)%'
```

```
# .env
ANALYTICS_CREDENTIALS_PATH=/secure/path/to/credentials.json

# .env.local (development)
ANALYTICS_CREDENTIALS_PATH=config/credentials/analytics-dev.json
```

### Error Handling

The bundle validates the credentials file and provides clear error messages for common issues:

- File not found or unreadable 
- Invalid JSON format
- Missing required fields
- Incorrect service account format

If you encounter credential errors, check:

1. That the file exists at the specified path
2. That the file has proper read permissions
3. That the file contains valid JSON with all required fields
4. That the service account has access to the Google Analytics property

## License

This bundle is released under the MIT License. See the included LICENSE file.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for information on how to contribute to this project.