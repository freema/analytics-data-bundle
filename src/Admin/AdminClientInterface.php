<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Admin;

use Freema\GA4AnalyticsDataBundle\Domain\CustomDimension;
use Freema\GA4AnalyticsDataBundle\Domain\DimensionScope;

/**
 * Interface for GA4 Admin API operations.
 *
 * Provides methods for managing custom dimensions, metrics, and other
 * property configuration in Google Analytics 4.
 */
interface AdminClientInterface
{
    // =========================================================================
    // Custom Dimensions - List & Get
    // =========================================================================

    /**
     * List all custom dimensions for the property.
     *
     * @return array<int, array{
     *     name: string,
     *     parameterName: string,
     *     displayName: string,
     *     description: string|null,
     *     scope: string,
     *     disallowAdsPersonalization: bool
     * }>
     */
    public function listCustomDimensions(): array;

    /**
     * Get a specific custom dimension by parameter name.
     *
     * @return array{
     *     name: string,
     *     parameterName: string,
     *     displayName: string,
     *     description: string|null,
     *     scope: string,
     *     disallowAdsPersonalization: bool
     * }|null Returns null if not found
     */
    public function getCustomDimension(string $parameterName): ?array;

    /**
     * Check if a custom dimension exists by parameter name.
     */
    public function customDimensionExists(string $parameterName): bool;

    // =========================================================================
    // Custom Dimensions - Create
    // =========================================================================

    /**
     * Create a new custom dimension.
     *
     * @param CustomDimension $dimension The dimension to create
     *
     * @return array{
     *     name: string,
     *     parameterName: string,
     *     displayName: string,
     *     description: string|null,
     *     scope: string,
     *     disallowAdsPersonalization: bool
     * }
     *
     * @throws \Freema\GA4AnalyticsDataBundle\Exception\DimensionAlreadyExistsException
     * @throws \Freema\GA4AnalyticsDataBundle\Exception\AdminApiException
     */
    public function createCustomDimension(CustomDimension $dimension): array;

    /**
     * Create a custom dimension with upsert behavior.
     * If the dimension already exists, skip it (no error thrown).
     *
     * @return array{
     *     created: bool,
     *     dimension: array{
     *         name: string,
     *         parameterName: string,
     *         displayName: string,
     *         description: string|null,
     *         scope: string,
     *         disallowAdsPersonalization: bool
     *     }
     * }
     */
    public function createCustomDimensionIfNotExists(CustomDimension $dimension): array;

    /**
     * Create multiple custom dimensions in batch.
     *
     * @param CustomDimension[] $dimensions
     *
     * @return array{
     *     created: array<int, array{parameterName: string, displayName: string}>,
     *     skipped: array<int, array{parameterName: string, reason: string}>,
     *     failed: array<int, array{parameterName: string, error: string}>
     * }
     */
    public function createCustomDimensionsBatch(array $dimensions): array;

    // =========================================================================
    // Custom Dimensions - Update & Archive
    // =========================================================================

    /**
     * Update an existing custom dimension.
     * Note: parameterName and scope cannot be changed after creation.
     *
     * @param string      $parameterName The parameter name of the dimension to update
     * @param string|null $displayName   New display name (null to keep current)
     * @param string|null $description   New description (null to keep current)
     *
     * @return array{
     *     name: string,
     *     parameterName: string,
     *     displayName: string,
     *     description: string|null,
     *     scope: string,
     *     disallowAdsPersonalization: bool
     * }
     *
     * @throws \Freema\GA4AnalyticsDataBundle\Exception\DimensionNotFoundException
     */
    public function updateCustomDimension(
        string $parameterName,
        ?string $displayName = null,
        ?string $description = null,
    ): array;

    /**
     * Archive a custom dimension.
     * Archived dimensions cannot be deleted but stop collecting data.
     *
     * @throws \Freema\GA4AnalyticsDataBundle\Exception\DimensionNotFoundException
     */
    public function archiveCustomDimension(string $parameterName): void;

    // =========================================================================
    // Validation & Sync
    // =========================================================================

    /**
     * Validate a dimension definition before creating.
     *
     * @return array{
     *     valid: bool,
     *     errors: string[],
     *     exists: bool
     * }
     */
    public function validateCustomDimension(CustomDimension $dimension): array;

    /**
     * Sync dimensions from a definition array.
     * Creates missing dimensions, reports existing ones.
     *
     * @param CustomDimension[] $definitions
     *
     * @return array{
     *     created: array<int, string>,
     *     existing: array<int, string>,
     *     failed: array<int, array{parameterName: string, error: string}>
     * }
     */
    public function syncCustomDimensions(array $definitions): array;

    /**
     * Find dimensions that are defined in code but missing in GA4.
     *
     * @param CustomDimension[] $definitions
     *
     * @return CustomDimension[]
     */
    public function findMissingDimensions(array $definitions): array;

    // =========================================================================
    // Custom Metrics (similar pattern)
    // =========================================================================

    /**
     * List all custom metrics for the property.
     *
     * @return array<int, array{
     *     name: string,
     *     parameterName: string,
     *     displayName: string,
     *     description: string|null,
     *     measurementUnit: string,
     *     scope: string
     * }>
     */
    public function listCustomMetrics(): array;

    // =========================================================================
    // Property Info
    // =========================================================================

    /**
     * Get the property ID this client is configured for.
     */
    public function getPropertyId(): string;
}
