<?php

declare(strict_types=1);

namespace PhpHive\Cli\Enums;

/**
 * Magento Edition Types.
 *
 * Defines available Magento editions with their licensing and features.
 *
 * Usage:
 * ```php
 * $edition = MagentoEdition::COMMUNITY->value; // 'community'
 * $name = MagentoEdition::COMMUNITY->getName(); // 'Community Edition'
 * $choices = MagentoEdition::choices(); // For prompts
 * ```
 */
enum MagentoEdition: string
{
    /**
     * Community Edition - Open Source, free version.
     *
     * Features:
     * - Core e-commerce functionality
     * - Open source and free
     * - Community support
     * - Self-hosted
     * - Suitable for small to medium businesses
     */
    case COMMUNITY = 'community';

    /**
     * Enterprise Edition - Commercial, licensed version.
     *
     * Features:
     * - All Community features plus:
     * - Advanced marketing tools
     * - Customer segmentation
     * - Staging and preview
     * - Page builder
     * - Official support
     * - Requires license
     */
    case ENTERPRISE = 'enterprise';

    /**
     * Get formatted choices for Laravel Prompts select().
     *
     * @return array<string, string> Formatted choices
     */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->value] = sprintf(
                '%s (%s)',
                $case->getName(),
                $case->getDescription()
            );
        }

        return $choices;
    }

    /**
     * Get the default edition.
     *
     * @return self Default edition
     */
    public static function default(): self
    {
        return self::COMMUNITY;
    }

    /**
     * Get all edition values as an array.
     *
     * @return array<string> Array of edition values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the display name for the edition.
     *
     * @return string Human-readable edition name
     */
    public function getName(): string
    {
        return match ($this) {
            self::COMMUNITY => 'Community Edition',
            self::ENTERPRISE => 'Enterprise Edition',
        };
    }

    /**
     * Get the description for the edition.
     *
     * @return string Edition description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::COMMUNITY => 'Open Source',
            self::ENTERPRISE => 'Commerce',
        };
    }

    /**
     * Get the Composer package name for the edition.
     *
     * @return string Composer package name
     */
    public function getComposerPackage(): string
    {
        return match ($this) {
            self::COMMUNITY => 'magento/project-community-edition',
            self::ENTERPRISE => 'magento/project-enterprise-edition',
        };
    }

    /**
     * Check if the edition requires a license.
     *
     * @return bool True if license is required
     */
    public function requiresLicense(): bool
    {
        return match ($this) {
            self::COMMUNITY => false,
            self::ENTERPRISE => true,
        };
    }
}
