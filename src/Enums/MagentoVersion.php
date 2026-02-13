<?php

declare(strict_types=1);

namespace PhpHive\Cli\Enums;

/**
 * Magento Version Enumeration.
 *
 * Defines all supported Magento 2 versions that can be installed via the CLI.
 * Each version has different PHP requirements, features, and support timelines.
 *
 * Magento Release Cycle:
 * - Patch releases: Quarterly (every 3 months)
 * - Minor releases: Annually
 * - Major releases: Every 2-3 years
 * - Support: 18 months of quality fixes, 3 years of security fixes
 *
 * Version Support Timeline:
 * - Magento 2.4.7: Released April 2024, supported until April 2027
 * - Magento 2.4.6: Released March 2023, supported until March 2026
 * - Magento 2.4.5: Released August 2022, supported until August 2025
 *
 * Important Requirements:
 * - Database: MySQL 8.0+ or MariaDB 10.4+
 * - Search: Elasticsearch 7.17+ or OpenSearch 1.2+
 * - Cache: Redis 6.0+ or Varnish 7.0+
 * - Message Queue: RabbitMQ 3.9+ (recommended for production)
 *
 * Usage:
 * ```php
 * // Get version number
 * $version = MagentoVersion::V2_4_7->value; // '2.4.7'
 *
 * // Get display label
 * $label = MagentoVersion::V2_4_7->getLabel(); // 'Magento 2.4.7 (Latest)'
 *
 * // Get PHP requirement
 * $php = MagentoVersion::V2_4_7->getPhpRequirement(); // '8.2-8.3'
 *
 * // Get composer constraint
 * $constraint = MagentoVersion::V2_4_7->getComposerConstraint(); // '2.4.7'
 *
 * // Get choices for prompts
 * $choices = MagentoVersion::choices();
 * ```
 *
 * @see https://experienceleague.adobe.com/docs/commerce-operations/release/versions.html
 * @see https://experienceleague.adobe.com/docs/commerce-operations/installation-guide/system-requirements.html
 */
enum MagentoVersion: string
{
    /**
     * Magento 2.4.7 (Latest).
     *
     * Released: April 2024
     * PHP Requirement: 8.2-8.3
     * MySQL: 8.0+
     * MariaDB: 10.6+
     * Elasticsearch: 8.x
     * OpenSearch: 2.x
     * Support: Quality fixes until October 2025, Security fixes until April 2027
     *
     * Key Features:
     * - PHP 8.3 support
     * - Performance improvements
     * - Security enhancements
     * - Updated dependencies
     * - Latest Adobe Commerce features
     *
     * Best for: New projects requiring latest features
     * Recommended: Yes (latest stable version)
     */
    case V2_4_7 = '2.4.7';

    /**
     * Magento 2.4.6.
     *
     * Released: March 2023
     * PHP Requirement: 8.1-8.2
     * MySQL: 8.0+
     * MariaDB: 10.4+
     * Elasticsearch: 7.17+ or 8.x
     * OpenSearch: 1.2+ or 2.x
     * Support: Quality fixes until September 2024, Security fixes until March 2026
     *
     * Key Features:
     * - PHP 8.2 support
     * - Stable and well-tested
     * - Extensive extension compatibility
     * - Mature ecosystem
     *
     * Best for: Projects requiring PHP 8.1 compatibility
     * Note: Quality fix support has ended, security fixes only
     */
    case V2_4_6 = '2.4.6';

    /**
     * Magento 2.4.5.
     *
     * Released: August 2022
     * PHP Requirement: 8.1
     * MySQL: 8.0+
     * MariaDB: 10.4+
     * Elasticsearch: 7.16+ or 7.17
     * OpenSearch: 1.2+
     * Support: Quality fixes until February 2024, Security fixes until August 2025
     *
     * Key Features:
     * - PHP 8.1 support
     * - Stable version
     * - Good extension compatibility
     *
     * Best for: Legacy projects or specific extension requirements
     * Note: Quality fix support has ended, approaching end of security support
     */
    case V2_4_5 = '2.4.5';

    /**
     * Get choices array for CLI prompts.
     *
     * Returns an associative array suitable for use with Laravel Prompts
     * select() function. Format: ['version' => 'Display Label']
     *
     * Example output:
     * ```php
     * [
     *     '2.4.7' => 'Magento 2.4.7 (Latest)',
     *     '2.4.6' => 'Magento 2.4.6',
     *     '2.4.5' => 'Magento 2.4.5',
     * ]
     * ```
     *
     * @return array<string, string> Map of version => display label
     */
    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $case) {
            $choices[$case->value] = $case->getLabel();
        }

        return $choices;
    }

    /**
     * Get the default recommended version.
     *
     * Returns the version that should be selected by default in prompts.
     * Currently defaults to V2_4_7 (latest) for new projects.
     *
     * @return self Default version
     */
    public static function default(): self
    {
        return self::V2_4_7;
    }

    /**
     * Get the display label for CLI prompts.
     *
     * Returns a formatted label indicating the version and its status
     * (Latest or standard version).
     *
     * @return string Display label (e.g., 'Magento 2.4.7 (Latest)')
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::V2_4_7 => 'Magento 2.4.7 (Latest)',
            self::V2_4_6 => 'Magento 2.4.6',
            self::V2_4_5 => 'Magento 2.4.5',
        };
    }

    /**
     * Get the PHP version requirement.
     *
     * Returns the supported PHP version range for this Magento version.
     * Used for validation and documentation.
     *
     * @return string PHP version range (e.g., '8.2-8.3', '8.1-8.2')
     */
    public function getPhpRequirement(): string
    {
        return match ($this) {
            self::V2_4_7 => '8.2-8.3',
            self::V2_4_6 => '8.1-8.2',
            self::V2_4_5 => '8.1',
        };
    }

    /**
     * Get the minimum MySQL version requirement.
     *
     * Returns the minimum MySQL version required for this Magento version.
     *
     * @return string MySQL version (e.g., '8.0')
     */
    public function getMysqlRequirement(): string
    {
        return '8.0';
    }

    /**
     * Get the minimum MariaDB version requirement.
     *
     * Returns the minimum MariaDB version required for this Magento version.
     *
     * @return string MariaDB version (e.g., '10.4', '10.6')
     */
    public function getMariaDbRequirement(): string
    {
        return match ($this) {
            self::V2_4_7 => '10.6',
            self::V2_4_6 => '10.4',
            self::V2_4_5 => '10.4',
        };
    }

    /**
     * Get supported Elasticsearch versions.
     *
     * Returns an array of supported Elasticsearch versions for this Magento version.
     *
     * @return array<string> Elasticsearch versions
     */
    public function getElasticsearchVersions(): array
    {
        return match ($this) {
            self::V2_4_7 => ['8.x'],
            self::V2_4_6 => ['7.17', '8.x'],
            self::V2_4_5 => ['7.16', '7.17'],
        };
    }

    /**
     * Get supported OpenSearch versions.
     *
     * Returns an array of supported OpenSearch versions for this Magento version.
     *
     * @return array<string> OpenSearch versions
     */
    public function getOpenSearchVersions(): array
    {
        return match ($this) {
            self::V2_4_7 => ['2.x'],
            self::V2_4_6 => ['1.2', '2.x'],
            self::V2_4_5 => ['1.2'],
        };
    }

    /**
     * Get the composer version constraint.
     *
     * Returns the exact version used in composer create-project command.
     * Magento uses exact versions, not wildcards.
     *
     * @return string Composer version constraint
     */
    public function getComposerConstraint(): string
    {
        return $this->value;
    }

    /**
     * Get the composer create-project command.
     *
     * Returns the full composer command to create a new Magento project
     * with this version. Requires Magento Marketplace authentication.
     *
     * @param  string $directory Target directory
     * @return string Composer create-project command
     */
    public function getCreateProjectCommand(string $directory): string
    {
        return "composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition:{$this->value} {$directory} --no-interaction";
    }

    /**
     * Check if this version requires Elasticsearch or OpenSearch.
     *
     * All Magento 2.4.x versions require a search engine.
     *
     * @return bool Always true for Magento 2.4.x
     */
    public function requiresSearchEngine(): bool
    {
        return true;
    }

    /**
     * Check if this version supports PHP 8.3.
     *
     * @return bool True if PHP 8.3 is supported
     */
    public function supportsPhp83(): bool
    {
        return $this === self::V2_4_7;
    }
}
