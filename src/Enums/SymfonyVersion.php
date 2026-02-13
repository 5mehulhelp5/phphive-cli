<?php

declare(strict_types=1);

namespace PhpHive\Cli\Enums;

/**
 * Symfony Version Enumeration.
 *
 * Defines all supported Symfony versions that can be installed via the CLI.
 * Each version has different PHP requirements, features, and support timelines.
 *
 * Symfony Release Cycle:
 * - Major releases: Every 2 years (November)
 * - Minor releases: Every 6 months (May and November)
 * - LTS (Long Term Support): Every 2 years
 * - Bug fixes: 8 months for standard, 3 years for LTS
 * - Security fixes: 14 months for standard, 4 years for LTS
 *
 * Version Support Timeline (as of 2026):
 * - Symfony 7.4 (LTS): Released Nov 2025, supported until Nov 2029
 * - Symfony 7.3: Released May 2025, supported until Jan 2026
 * - Symfony 7.2: Released Nov 2024, supported until Jul 2025
 * - Symfony 6.4 (LTS): Released Nov 2023, supported until Nov 2027
 *
 * Important Notes:
 * - Symfony 7.1 is NOT an LTS version (contrary to previous incorrect labeling)
 * - LTS versions are released every 2 years in November
 * - Current LTS versions: 6.4 and 7.4
 *
 * Usage:
 * ```php
 * // Get version number
 * $version = SymfonyVersion::V7_4->value; // '7.4'
 *
 * // Get display label
 * $label = SymfonyVersion::V7_4->getLabel(); // 'Symfony 7.4 (LTS) — Recommended'
 *
 * // Get PHP requirement
 * $php = SymfonyVersion::V7_4->getPhpRequirement(); // '8.2'
 *
 * // Check if LTS
 * $isLts = SymfonyVersion::V7_4->isLts(); // true
 *
 * // Get composer constraint
 * $constraint = SymfonyVersion::V7_4->getComposerConstraint(); // '7.4.*'
 *
 * // Get choices for prompts
 * $choices = SymfonyVersion::choices();
 * ```
 *
 * Future Improvement:
 * Implement dynamic version resolution via SymfonyVersionProvider that queries
 * Packagist API to get available versions automatically. This would eliminate
 * the need for manual updates when new Symfony versions are released.
 *
 * @see https://symfony.com/releases
 * @see https://symfony.com/doc/current/contributing/community/releases.html
 */
enum SymfonyVersion: string
{
    /**
     * Symfony 7.4 (LTS - Long Term Support).
     *
     * Released: November 2025
     * PHP Requirement: 8.2+
     * Support: Bug fixes until November 2028, Security fixes until November 2029
     *
     * Key Features:
     * - Latest LTS with extended support
     * - All Symfony 7.x improvements
     * - Performance optimizations
     * - Enhanced developer experience
     * - Stable API for long-term projects
     *
     * Best for: Production applications requiring long-term stability
     * Recommended: Yes (current LTS with 4 years of security support)
     */
    case V7_4 = '7.4';

    /**
     * Symfony 7.3 (Standard Release).
     *
     * Released: May 2025
     * PHP Requirement: 8.2+
     * Support: Bug fixes until January 2026, Security fixes until July 2026
     *
     * Key Features:
     * - Latest features and improvements
     * - Performance enhancements
     * - New components and features
     * - Updated dependencies
     *
     * Best for: Projects that can upgrade frequently
     * Note: Shorter support timeline than LTS versions
     */
    case V7_3 = '7.3';

    /**
     * Symfony 7.2 (Standard Release).
     *
     * Released: November 2024
     * PHP Requirement: 8.2+
     * Support: Bug fixes until July 2025, Security fixes until January 2026
     *
     * Key Features:
     * - Stable Symfony 7.x features
     * - Mature ecosystem
     * - Well-tested components
     *
     * Best for: Projects started in late 2024/early 2025
     * Note: Consider upgrading to 7.4 LTS for longer support
     */
    case V7_2 = '7.2';

    /**
     * Symfony 6.4 (Previous LTS).
     *
     * Released: November 2023
     * PHP Requirement: 8.1+
     * Support: Bug fixes until November 2026, Security fixes until November 2027
     *
     * Key Features:
     * - Mature and stable LTS version
     * - PHP 8.1 compatibility
     * - Extensive ecosystem support
     * - Well-documented and tested
     *
     * Best for: Projects requiring PHP 8.1 compatibility
     * Note: Still has 2+ years of security support remaining
     */
    case V6_4 = '6.4';

    /**
     * Get choices array for CLI prompts.
     *
     * Returns an associative array suitable for use with Laravel Prompts
     * select() function. Format: ['version' => 'Display Label']
     *
     * Example output:
     * ```php
     * [
     *     '7.4' => 'Symfony 7.4 (LTS) — Recommended',
     *     '7.3' => 'Symfony 7.3',
     *     '7.2' => 'Symfony 7.2',
     *     '6.4' => 'Symfony 6.4 (LTS)',
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
     * Currently defaults to V7_4 (current LTS) for production stability.
     *
     * @return self Default version
     */
    public static function default(): self
    {
        return self::V7_4;
    }

    /**
     * Get the latest LTS version.
     *
     * Returns the most recent LTS version available.
     * Useful for recommendations and default selections.
     *
     * @return self Latest LTS version
     */
    public static function latestLts(): self
    {
        return self::V7_4;
    }

    /**
     * Get the display label for CLI prompts.
     *
     * Returns a formatted label indicating the version and its status
     * (LTS, Recommended, or standard version).
     *
     * @return string Display label (e.g., 'Symfony 7.4 (LTS) — Recommended')
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::V7_4 => 'Symfony 7.4 (LTS) — Recommended',
            self::V7_3 => 'Symfony 7.3',
            self::V7_2 => 'Symfony 7.2',
            self::V6_4 => 'Symfony 6.4 (LTS)',
        };
    }

    /**
     * Get the minimum PHP version requirement.
     *
     * Returns the minimum PHP version required to run this Symfony version.
     * Used for validation and documentation.
     *
     * @return string PHP version (e.g., '8.2', '8.1')
     */
    public function getPhpRequirement(): string
    {
        return match ($this) {
            self::V7_4 => '8.2',
            self::V7_3 => '8.2',
            self::V7_2 => '8.2',
            self::V6_4 => '8.1',
        };
    }

    /**
     * Check if this is an LTS (Long Term Support) version.
     *
     * LTS versions receive extended bug fixes (3 years) and security fixes (4 years).
     * Recommended for production applications requiring long-term stability.
     *
     * Symfony LTS versions are released every 2 years in November.
     * Current LTS versions: 6.4 (Nov 2023) and 7.4 (Nov 2025)
     *
     * @return bool True if LTS version
     */
    public function isLts(): bool
    {
        return match ($this) {
            self::V7_4 => true,
            self::V6_4 => true,
            self::V7_3 => false,
            self::V7_2 => false,
        };
    }

    /**
     * Get the composer version constraint.
     *
     * Returns the version constraint used in composer create-project command.
     * Format: {major}.{minor}.* to get the latest patch version.
     *
     * Example: '7.4.*' installs Symfony 7.4.0, 7.4.1, etc. (latest 7.4.x)
     *
     * @return string Composer version constraint
     */
    public function getComposerConstraint(): string
    {
        return "{$this->value}.*";
    }

    /**
     * Get the composer create-project command for skeleton.
     *
     * Returns the full composer command to create a new Symfony skeleton project
     * with this version. Skeleton is minimal and suitable for APIs and microservices.
     *
     * @param  string $directory Target directory (use '.' for current directory)
     * @return string Composer create-project command
     */
    public function getCreateSkeletonCommand(string $directory = '.'): string
    {
        return "composer create-project symfony/skeleton:{$this->getComposerConstraint()} {$directory}";
    }

    /**
     * Get the composer create-project command for webapp.
     *
     * Returns the full composer command to create a new Symfony webapp project
     * with this version. Webapp includes Twig, forms, security, and other web features.
     *
     * @param  string $directory Target directory (use '.' for current directory)
     * @return string Composer create-project command
     */
    public function getCreateWebappCommand(string $directory = '.'): string
    {
        return "composer create-project symfony/webapp:{$this->getComposerConstraint()} {$directory}";
    }
}
