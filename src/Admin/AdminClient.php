<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Admin;

use Freema\GA4AnalyticsDataBundle\Cache\AnalyticsCache;
use Freema\GA4AnalyticsDataBundle\Domain\CustomDimension;
use Freema\GA4AnalyticsDataBundle\Domain\DimensionScope;
use Freema\GA4AnalyticsDataBundle\Exception\AdminApiException;
use Freema\GA4AnalyticsDataBundle\Exception\DimensionAlreadyExistsException;
use Freema\GA4AnalyticsDataBundle\Exception\DimensionNotFoundException;
use Freema\GA4AnalyticsDataBundle\Http\GoogleAdminClientFactory;
use Google\Analytics\Admin\V1beta\Client\AnalyticsAdminServiceClient;
use Google\Analytics\Admin\V1beta\CreateCustomDimensionRequest;
use Google\Analytics\Admin\V1beta\CustomDimension as GoogleCustomDimension;
use Google\Analytics\Admin\V1beta\ListCustomDimensionsRequest;
use Google\Analytics\Admin\V1beta\ListCustomMetricsRequest;
use Google\Analytics\Admin\V1beta\UpdateCustomDimensionRequest;
use Google\Protobuf\FieldMask;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Client for GA4 Admin API operations.
 *
 * Provides methods for managing custom dimensions, metrics, and other
 * property configuration in Google Analytics 4.
 */
class AdminClient implements AdminClientInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ?AnalyticsAdminServiceClient $adminClient = null;
    private array $config;
    private AnalyticsCache $cache;
    private GoogleAdminClientFactory $clientFactory;

    /** @var array<string, array>|null Cached dimensions indexed by parameterName */
    private ?array $dimensionsCache = null;

    public function __construct(
        GoogleAdminClientFactory $clientFactory,
        array $config,
        AnalyticsCache $cache,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->config = $config;
        $this->clientFactory = $clientFactory;
        $this->cache = $cache;
    }

    public function getPropertyId(): string
    {
        return $this->config['property_id'];
    }

    // =========================================================================
    // Custom Dimensions - List & Get
    // =========================================================================

    public function listCustomDimensions(): array
    {
        $cacheKey = 'admin_custom_dimensions_'.$this->getPropertyId();

        return $this->cache->get($cacheKey, function () {
            try {
                $parent = $this->getPropertyPath();
                $request = new ListCustomDimensionsRequest();
                $request->setParent($parent);

                $dimensions = [];
                $response = $this->getAdminClient()->listCustomDimensions($request);

                foreach ($response->iterateAllElements() as $dimension) {
                    $dimensions[] = $this->convertGoogleDimensionToArray($dimension);
                }

                return $dimensions;
            } catch (\Exception $e) {
                $this->logger?->error('Failed to list custom dimensions', [
                    'error' => $e->getMessage(),
                    'property_id' => $this->getPropertyId(),
                ]);

                throw new AdminApiException('Failed to list custom dimensions: '.$e->getMessage(), $this->getPropertyId(), 0, $e);
            }
        });
    }

    public function getCustomDimension(string $parameterName): ?array
    {
        $dimensions = $this->getDimensionsIndexed();

        return $dimensions[$parameterName] ?? null;
    }

    public function customDimensionExists(string $parameterName): bool
    {
        return null !== $this->getCustomDimension($parameterName);
    }

    // =========================================================================
    // Custom Dimensions - Create
    // =========================================================================

    public function createCustomDimension(CustomDimension $dimension): array
    {
        // Check if already exists
        if ($this->customDimensionExists($dimension->parameterName)) {
            throw new DimensionAlreadyExistsException($dimension->parameterName, $this->getPropertyId());
        }

        try {
            $googleDimension = $this->convertToGoogleDimension($dimension);

            $request = new CreateCustomDimensionRequest();
            $request->setParent($this->getPropertyPath());
            $request->setCustomDimension($googleDimension);

            $result = $this->getAdminClient()->createCustomDimension($request);

            // Invalidate cache
            $this->invalidateDimensionsCache();

            $this->logger?->info('Created custom dimension', [
                'parameterName' => $dimension->parameterName,
                'displayName' => $dimension->displayName,
                'scope' => $dimension->scope->value,
                'property_id' => $this->getPropertyId(),
            ]);

            return $this->convertGoogleDimensionToArray($result);
        } catch (\Exception $e) {
            $this->logger?->error('Failed to create custom dimension', [
                'error' => $e->getMessage(),
                'parameterName' => $dimension->parameterName,
                'property_id' => $this->getPropertyId(),
            ]);

            throw new AdminApiException(sprintf('Failed to create custom dimension "%s": %s', $dimension->parameterName, $e->getMessage()), $this->getPropertyId(), 0, $e);
        }
    }

    public function createCustomDimensionIfNotExists(CustomDimension $dimension): array
    {
        $existing = $this->getCustomDimension($dimension->parameterName);

        if (null !== $existing) {
            return [
                'created' => false,
                'dimension' => $existing,
            ];
        }

        return [
            'created' => true,
            'dimension' => $this->createCustomDimension($dimension),
        ];
    }

    public function createCustomDimensionsBatch(array $dimensions): array
    {
        $result = [
            'created' => [],
            'skipped' => [],
            'failed' => [],
        ];

        // Get all existing dimensions upfront
        $existing = $this->getDimensionsIndexed();

        foreach ($dimensions as $dimension) {
            if (!$dimension instanceof CustomDimension) {
                $result['failed'][] = [
                    'parameterName' => 'unknown',
                    'error' => 'Invalid dimension type, expected CustomDimension instance',
                ];
                continue;
            }

            // Skip if already exists
            if (isset($existing[$dimension->parameterName])) {
                $result['skipped'][] = [
                    'parameterName' => $dimension->parameterName,
                    'reason' => 'Already exists',
                ];
                continue;
            }

            try {
                $this->createCustomDimension($dimension);
                $result['created'][] = [
                    'parameterName' => $dimension->parameterName,
                    'displayName' => $dimension->displayName,
                ];
            } catch (\Exception $e) {
                $result['failed'][] = [
                    'parameterName' => $dimension->parameterName,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->logger?->info('Batch dimension creation completed', [
            'created' => count($result['created']),
            'skipped' => count($result['skipped']),
            'failed' => count($result['failed']),
            'property_id' => $this->getPropertyId(),
        ]);

        return $result;
    }

    // =========================================================================
    // Custom Dimensions - Update & Archive
    // =========================================================================

    public function updateCustomDimension(
        string $parameterName,
        ?string $displayName = null,
        ?string $description = null,
    ): array {
        $existing = $this->getCustomDimension($parameterName);

        if (null === $existing) {
            throw new DimensionNotFoundException($parameterName, $this->getPropertyId());
        }

        try {
            $googleDimension = new GoogleCustomDimension();
            $googleDimension->setName($existing['name']);

            $updateFields = [];

            if (null !== $displayName) {
                $googleDimension->setDisplayName($displayName);
                $updateFields[] = 'display_name';
            }

            if (null !== $description) {
                $googleDimension->setDescription($description);
                $updateFields[] = 'description';
            }

            if (empty($updateFields)) {
                return $existing;
            }

            $request = new UpdateCustomDimensionRequest();
            $request->setCustomDimension($googleDimension);
            $request->setUpdateMask(new FieldMask(['paths' => $updateFields]));

            $result = $this->getAdminClient()->updateCustomDimension($request);

            // Invalidate cache
            $this->invalidateDimensionsCache();

            $this->logger?->info('Updated custom dimension', [
                'parameterName' => $parameterName,
                'updatedFields' => $updateFields,
                'property_id' => $this->getPropertyId(),
            ]);

            return $this->convertGoogleDimensionToArray($result);
        } catch (\Exception $e) {
            $this->logger?->error('Failed to update custom dimension', [
                'error' => $e->getMessage(),
                'parameterName' => $parameterName,
                'property_id' => $this->getPropertyId(),
            ]);

            throw new AdminApiException(sprintf('Failed to update custom dimension "%s": %s', $parameterName, $e->getMessage()), $this->getPropertyId(), 0, $e);
        }
    }

    public function archiveCustomDimension(string $parameterName): void
    {
        $existing = $this->getCustomDimension($parameterName);

        if (null === $existing) {
            throw new DimensionNotFoundException($parameterName, $this->getPropertyId());
        }

        try {
            $request = new \Google\Analytics\Admin\V1beta\ArchiveCustomDimensionRequest();
            $request->setName($existing['name']);

            $this->getAdminClient()->archiveCustomDimension($request);

            // Invalidate cache
            $this->invalidateDimensionsCache();

            $this->logger?->info('Archived custom dimension', [
                'parameterName' => $parameterName,
                'property_id' => $this->getPropertyId(),
            ]);
        } catch (\Exception $e) {
            $this->logger?->error('Failed to archive custom dimension', [
                'error' => $e->getMessage(),
                'parameterName' => $parameterName,
                'property_id' => $this->getPropertyId(),
            ]);

            throw new AdminApiException(sprintf('Failed to archive custom dimension "%s": %s', $parameterName, $e->getMessage()), $this->getPropertyId(), 0, $e);
        }
    }

    // =========================================================================
    // Validation & Sync
    // =========================================================================

    public function validateCustomDimension(CustomDimension $dimension): array
    {
        $errors = CustomDimension::validate(
            $dimension->parameterName,
            $dimension->displayName,
            $dimension->description
        );

        $exists = $this->customDimensionExists($dimension->parameterName);

        return [
            'valid' => empty($errors) && !$exists,
            'errors' => $errors,
            'exists' => $exists,
        ];
    }

    public function syncCustomDimensions(array $definitions): array
    {
        $result = [
            'created' => [],
            'existing' => [],
            'failed' => [],
        ];

        $existingDimensions = $this->getDimensionsIndexed();

        foreach ($definitions as $definition) {
            if (!$definition instanceof CustomDimension) {
                continue;
            }

            if (isset($existingDimensions[$definition->parameterName])) {
                $result['existing'][] = $definition->parameterName;
                continue;
            }

            try {
                $this->createCustomDimension($definition);
                $result['created'][] = $definition->parameterName;
            } catch (\Exception $e) {
                $result['failed'][] = [
                    'parameterName' => $definition->parameterName,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->logger?->info('Dimension sync completed', [
            'created' => count($result['created']),
            'existing' => count($result['existing']),
            'failed' => count($result['failed']),
            'property_id' => $this->getPropertyId(),
        ]);

        return $result;
    }

    public function findMissingDimensions(array $definitions): array
    {
        $existing = $this->getDimensionsIndexed();
        $missing = [];

        foreach ($definitions as $definition) {
            if (!$definition instanceof CustomDimension) {
                continue;
            }

            if (!isset($existing[$definition->parameterName])) {
                $missing[] = $definition;
            }
        }

        return $missing;
    }

    // =========================================================================
    // Custom Metrics
    // =========================================================================

    public function listCustomMetrics(): array
    {
        $cacheKey = 'admin_custom_metrics_'.$this->getPropertyId();

        return $this->cache->get($cacheKey, function () {
            try {
                $parent = $this->getPropertyPath();
                $request = new ListCustomMetricsRequest();
                $request->setParent($parent);

                $metrics = [];
                $response = $this->getAdminClient()->listCustomMetrics($request);

                foreach ($response->iterateAllElements() as $metric) {
                    $metrics[] = [
                        'name' => $metric->getName(),
                        'parameterName' => $metric->getParameterName(),
                        'displayName' => $metric->getDisplayName(),
                        'description' => $metric->getDescription() ?: null,
                        'measurementUnit' => $metric->getMeasurementUnit(),
                        'scope' => $this->convertMetricScope($metric->getScope()),
                    ];
                }

                return $metrics;
            } catch (\Exception $e) {
                $this->logger?->error('Failed to list custom metrics', [
                    'error' => $e->getMessage(),
                    'property_id' => $this->getPropertyId(),
                ]);

                throw new AdminApiException('Failed to list custom metrics: '.$e->getMessage(), $this->getPropertyId(), 0, $e);
            }
        });
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    private function getAdminClient(): AnalyticsAdminServiceClient
    {
        if (null === $this->adminClient) {
            $this->adminClient = $this->clientFactory->createAdminClient($this->config);
        }

        return $this->adminClient;
    }

    private function getPropertyPath(): string
    {
        return sprintf('properties/%s', $this->getPropertyId());
    }

    /**
     * Get dimensions indexed by parameterName for quick lookups.
     *
     * @return array<string, array>
     */
    private function getDimensionsIndexed(): array
    {
        if (null === $this->dimensionsCache) {
            $dimensions = $this->listCustomDimensions();
            $this->dimensionsCache = [];

            foreach ($dimensions as $dimension) {
                $this->dimensionsCache[$dimension['parameterName']] = $dimension;
            }
        }

        return $this->dimensionsCache;
    }

    private function invalidateDimensionsCache(): void
    {
        $this->dimensionsCache = null;
        // Also invalidate the persistent cache
        $cacheKey = 'admin_custom_dimensions_'.$this->getPropertyId();
        $this->cache->delete($cacheKey);
    }

    private function convertToGoogleDimension(CustomDimension $dimension): GoogleCustomDimension
    {
        $googleDimension = new GoogleCustomDimension();
        $googleDimension->setParameterName($dimension->parameterName);
        $googleDimension->setDisplayName($dimension->displayName);
        $googleDimension->setScope($this->convertScopeToGoogle($dimension->scope));

        if (null !== $dimension->description) {
            $googleDimension->setDescription($dimension->description);
        }

        $googleDimension->setDisallowAdsPersonalization($dimension->disallowAdsPersonalization);

        return $googleDimension;
    }

    private function convertGoogleDimensionToArray(GoogleCustomDimension $dimension): array
    {
        return [
            'name' => $dimension->getName(),
            'parameterName' => $dimension->getParameterName(),
            'displayName' => $dimension->getDisplayName(),
            'description' => $dimension->getDescription() ?: null,
            'scope' => $this->convertScopeFromGoogle($dimension->getScope()),
            'disallowAdsPersonalization' => $dimension->getDisallowAdsPersonalization(),
        ];
    }

    private function convertScopeToGoogle(DimensionScope $scope): int
    {
        return match ($scope) {
            DimensionScope::EVENT => GoogleCustomDimension\DimensionScope::EVENT,
            DimensionScope::USER => GoogleCustomDimension\DimensionScope::USER,
            DimensionScope::ITEM => GoogleCustomDimension\DimensionScope::ITEM,
        };
    }

    private function convertScopeFromGoogle(int $scope): string
    {
        return match ($scope) {
            GoogleCustomDimension\DimensionScope::EVENT => DimensionScope::EVENT->value,
            GoogleCustomDimension\DimensionScope::USER => DimensionScope::USER->value,
            GoogleCustomDimension\DimensionScope::ITEM => DimensionScope::ITEM->value,
            default => 'UNKNOWN',
        };
    }

    private function convertMetricScope(int $scope): string
    {
        // Custom metrics only support EVENT scope in GA4
        return 'EVENT';
    }
}
