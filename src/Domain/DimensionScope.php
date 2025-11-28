<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Domain;

/**
 * Enum representing valid scopes for GA4 custom dimensions.
 *
 * @see https://developers.google.com/analytics/devguides/config/admin/v1/rest/v1beta/properties.customDimensions#DimensionScope
 */
enum DimensionScope: string
{
    /**
     * Event-level dimension. Captured with each event.
     * Example: button_name, page_section, video_title
     */
    case EVENT = 'EVENT';

    /**
     * User-level dimension. Persists across sessions for a user.
     * Example: membership_tier, customer_type, preferred_language
     */
    case USER = 'USER';

    /**
     * Item-level dimension. Used for e-commerce item properties.
     * Example: item_category, item_brand, item_variant
     */
    case ITEM = 'ITEM';

    /**
     * Get human-readable description for this scope.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::EVENT => 'Event-level dimension, captured with each event',
            self::USER => 'User-level dimension, persists across sessions',
            self::ITEM => 'Item-level dimension for e-commerce items',
        };
    }

    /**
     * Get all valid scope values as array.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(fn (self $scope) => $scope->value, self::cases());
    }

    /**
     * Check if a string is a valid scope value.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    /**
     * Create from string with helpful error message.
     *
     * @throws \InvalidArgumentException
     */
    public static function fromString(string $value): self
    {
        $scope = self::tryFrom($value);

        if (null === $scope) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid scope "%s". Valid values: %s',
                $value,
                implode(', ', self::values())
            ));
        }

        return $scope;
    }
}
