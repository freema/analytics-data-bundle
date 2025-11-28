<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Domain;

use Freema\GA4AnalyticsDataBundle\Exception\ValidationException;

/**
 * Value object representing a GA4 custom dimension definition.
 *
 * Constraints:
 * - displayName: max 82 characters
 * - parameterName: max 24 characters, must match [a-zA-Z][a-zA-Z0-9_]*
 * - description: max 150 characters (optional)
 */
final class CustomDimension
{
    public const MAX_DISPLAY_NAME_LENGTH = 82;
    public const MAX_PARAMETER_NAME_LENGTH = 24;
    public const MAX_DESCRIPTION_LENGTH = 150;
    public const PARAMETER_NAME_PATTERN = '/^[a-zA-Z][a-zA-Z0-9_]*$/';

    private function __construct(
        public readonly string $parameterName,
        public readonly string $displayName,
        public readonly DimensionScope $scope,
        public readonly ?string $description = null,
        public readonly bool $disallowAdsPersonalization = false,
    ) {
    }

    /**
     * Create a new custom dimension definition.
     *
     * @param string              $parameterName             The event parameter name (e.g., 'button_name')
     * @param string              $displayName               Human-readable name shown in GA4 UI
     * @param DimensionScope      $scope                     The scope of the dimension
     * @param string|null         $description               Optional description
     * @param bool                $disallowAdsPersonalization Whether to exclude from ads personalization
     *
     * @throws ValidationException If validation fails
     */
    public static function create(
        string $parameterName,
        string $displayName,
        DimensionScope $scope,
        ?string $description = null,
        bool $disallowAdsPersonalization = false,
    ): self {
        $errors = self::validate($parameterName, $displayName, $description);

        if (!empty($errors)) {
            throw new ValidationException(
                'Custom dimension validation failed: '.implode('; ', $errors),
                $errors
            );
        }

        return new self(
            $parameterName,
            $displayName,
            $scope,
            $description,
            $disallowAdsPersonalization
        );
    }

    /**
     * Create from array (useful for bulk operations).
     *
     * @param array{
     *     parameterName: string,
     *     displayName: string,
     *     scope: string|DimensionScope,
     *     description?: string|null,
     *     disallowAdsPersonalization?: bool
     * } $data
     *
     * @throws ValidationException
     */
    public static function fromArray(array $data): self
    {
        $scope = $data['scope'] instanceof DimensionScope
            ? $data['scope']
            : DimensionScope::fromString($data['scope']);

        return self::create(
            $data['parameterName'],
            $data['displayName'],
            $scope,
            $data['description'] ?? null,
            $data['disallowAdsPersonalization'] ?? false
        );
    }

    /**
     * Validate dimension parameters without throwing.
     *
     * @return string[] Array of error messages, empty if valid
     */
    public static function validate(
        string $parameterName,
        string $displayName,
        ?string $description = null,
    ): array {
        $errors = [];

        // Validate parameterName
        if (empty($parameterName)) {
            $errors[] = 'parameterName is required';
        } elseif (strlen($parameterName) > self::MAX_PARAMETER_NAME_LENGTH) {
            $errors[] = sprintf(
                'parameterName exceeds maximum length of %d characters (got %d)',
                self::MAX_PARAMETER_NAME_LENGTH,
                strlen($parameterName)
            );
        } elseif (!preg_match(self::PARAMETER_NAME_PATTERN, $parameterName)) {
            $errors[] = sprintf(
                'parameterName "%s" is invalid. Must start with a letter and contain only letters, numbers, and underscores',
                $parameterName
            );
        }

        // Validate displayName
        if (empty($displayName)) {
            $errors[] = 'displayName is required';
        } elseif (strlen($displayName) > self::MAX_DISPLAY_NAME_LENGTH) {
            $errors[] = sprintf(
                'displayName exceeds maximum length of %d characters (got %d)',
                self::MAX_DISPLAY_NAME_LENGTH,
                strlen($displayName)
            );
        }

        // Validate description
        if (null !== $description && strlen($description) > self::MAX_DESCRIPTION_LENGTH) {
            $errors[] = sprintf(
                'description exceeds maximum length of %d characters (got %d)',
                self::MAX_DESCRIPTION_LENGTH,
                strlen($description)
            );
        }

        return $errors;
    }

    /**
     * Check if this dimension definition is valid.
     */
    public function isValid(): bool
    {
        return empty(self::validate($this->parameterName, $this->displayName, $this->description));
    }

    /**
     * Convert to array for serialization.
     *
     * @return array{
     *     parameterName: string,
     *     displayName: string,
     *     scope: string,
     *     description: string|null,
     *     disallowAdsPersonalization: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'parameterName' => $this->parameterName,
            'displayName' => $this->displayName,
            'scope' => $this->scope->value,
            'description' => $this->description,
            'disallowAdsPersonalization' => $this->disallowAdsPersonalization,
        ];
    }

    /**
     * Get the event parameter name to use in tracking code.
     * For EVENT scope, it's the parameterName directly.
     * For USER scope, it needs to be set as a user property.
     */
    public function getTrackingParameter(): string
    {
        return $this->parameterName;
    }

    /**
     * Check if this dimension matches another by parameter name.
     */
    public function matchesParameterName(string $parameterName): bool
    {
        return $this->parameterName === $parameterName;
    }
}
