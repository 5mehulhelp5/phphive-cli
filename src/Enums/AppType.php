<?php

declare(strict_types=1);

namespace PhpHive\Cli\Enums;

use PhpHive\Cli\AppTypes\Laravel\LaravelAppType;
use PhpHive\Cli\AppTypes\Magento\MagentoAppType;
use PhpHive\Cli\AppTypes\Skeleton\SkeletonAppType;
use PhpHive\Cli\AppTypes\Symfony\SymfonyAppType;
use PhpHive\Cli\Contracts\AppTypeInterface;

/**
 * Application Type Enumeration.
 *
 * Defines all supported application types in the PhpHive monorepo CLI.
 * Each app type represents a different PHP framework or application structure
 * that can be scaffolded and managed by the CLI.
 *
 * Application types determine:
 * - Installation commands (composer create-project)
 * - Configuration prompts and options
 * - Stub templates used for scaffolding
 * - Post-installation setup steps
 * - Infrastructure requirements
 * - Development workflow
 *
 * Usage:
 * ```php
 * // Get app type identifier
 * $type = AppType::LARAVEL->value; // 'laravel'
 *
 * // Get display name
 * $name = AppType::LARAVEL->getName(); // 'Laravel'
 *
 * // Get description
 * $desc = AppType::LARAVEL->getDescription(); // 'Full-stack PHP framework'
 *
 * // Check if valid
 * $valid = AppType::tryFrom('laravel') !== null;
 *
 * // Get all types
 * $all = AppType::cases();
 *
 * // Get choices for prompts
 * $choices = AppType::choices();
 * ```
 *
 * Adding a new app type:
 * 1. Add new case to this enum
 * 2. Implement getName() and getDescription() for the new case
 * 3. Create AppType class implementing AppTypeInterface
 * 4. Add to AppTypeFactory::getAvailableTypes()
 * 5. Create stub templates in cli/stubs/apps/{type}/
 *
 * @see AppTypeInterface
 * @see \PhpHive\Cli\Factories\AppTypeFactory
 */
enum AppType: string
{
    /**
     * Laravel Application.
     *
     * Full-stack PHP framework with elegant syntax and rich ecosystem.
     * Best for: Web applications, APIs, SaaS platforms, admin panels.
     *
     * Features:
     * - Eloquent ORM for database interactions
     * - Blade templating engine
     * - Built-in authentication and authorization
     * - Queue system for background jobs
     * - Event broadcasting and real-time features
     * - Extensive package ecosystem
     *
     * Versions: 10 (LTS), 11 (LTS), 12 (Latest)
     * PHP Requirements: 8.1+ (v10), 8.2+ (v11, v12)
     */
    case LARAVEL = 'laravel';

    /**
     * Symfony Application.
     *
     * High-performance PHP framework with enterprise-grade components.
     * Best for: Complex applications, APIs, microservices, enterprise systems.
     *
     * Features:
     * - Doctrine ORM for database management
     * - Twig templating engine
     * - Symfony Flex for automatic configuration
     * - Messenger component for async processing
     * - Security component for authentication
     * - Highly modular and flexible architecture
     *
     * Versions: 6.4 (LTS), 7.2, 7.3, 7.4 (LTS)
     * PHP Requirements: 8.1+ (v6.4), 8.2+ (v7.x)
     */
    case SYMFONY = 'symfony';

    /**
     * Magento Application.
     *
     * Enterprise e-commerce platform with extensive features.
     * Best for: E-commerce stores, B2B platforms, multi-store setups.
     *
     * Features:
     * - Complete e-commerce functionality
     * - Multi-store and multi-language support
     * - Advanced catalog management
     * - Flexible pricing and promotions
     * - Integrated payment and shipping
     * - Extensive extension marketplace
     *
     * Versions: 2.4.5, 2.4.6, 2.4.7
     * PHP Requirements: 8.1-8.2 (v2.4.5-2.4.6), 8.2-8.3 (v2.4.7)
     * Database: MySQL 8.0+ or MariaDB 10.4+
     */
    case MAGENTO = 'magento';

    /**
     * Skeleton Application.
     *
     * Minimal PHP application structure without framework dependencies.
     * Best for: Libraries, CLI tools, microservices, custom applications.
     *
     * Features:
     * - PSR-4 autoloading
     * - Composer dependency management
     * - Optional PHPUnit for testing
     * - Optional quality tools (PHPStan, PHP CS Fixer)
     * - Clean architecture foundation
     * - No framework overhead
     *
     * PHP Requirements: 8.1+
     * Ideal for: Projects that don't need full framework features
     */
    case SKELETON = 'skeleton';

    /**
     * Get choices array for CLI prompts.
     *
     * Returns an associative array suitable for use with Laravel Prompts
     * select() function. Format: ['Display Name (Description)' => 'value']
     *
     * Example output:
     * ```php
     * [
     *     'Laravel (Full-stack PHP framework)' => 'laravel',
     *     'Symfony (High-performance PHP framework)' => 'symfony',
     *     'Magento (Enterprise e-commerce platform)' => 'magento',
     *     'Skeleton (Minimal PHP application)' => 'skeleton',
     * ]
     * ```
     *
     * @return array<string, string> Map of display label => value
     */
    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $case) {
            $label = "{$case->getName()} ({$case->getDescription()})";
            $choices[$label] = $case->value;
        }

        return $choices;
    }

    /**
     * Get all app type values as an array.
     *
     * Returns a simple array of all app type identifiers.
     * Useful for validation and documentation.
     *
     * Example output: ['laravel', 'symfony', 'magento', 'skeleton']
     *
     * @return array<string> List of app type identifiers
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Get the display name of the app type.
     *
     * Returns a human-readable name suitable for display in CLI prompts,
     * documentation, and user interfaces.
     *
     * @return string Display name (e.g., 'Laravel', 'Symfony')
     */
    public function getName(): string
    {
        return match ($this) {
            self::LARAVEL => 'Laravel',
            self::SYMFONY => 'Symfony',
            self::MAGENTO => 'Magento',
            self::SKELETON => 'Skeleton',
        };
    }

    /**
     * Get a brief description of the app type.
     *
     * Returns a short description explaining what the app type is best suited for.
     * Used in CLI prompts to help users choose the right app type.
     *
     * @return string Brief description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::LARAVEL => 'Full-stack PHP framework',
            self::SYMFONY => 'High-performance PHP framework',
            self::MAGENTO => 'Enterprise e-commerce platform',
            self::SKELETON => 'Minimal PHP application',
        };
    }

    /**
     * Get the fully qualified class name for this app type.
     *
     * Returns the class name of the AppType implementation that handles
     * scaffolding and configuration for this app type.
     *
     * @return class-string<AppTypeInterface>
     */
    public function getClassName(): string
    {
        return match ($this) {
            self::LARAVEL => LaravelAppType::class,
            self::SYMFONY => SymfonyAppType::class,
            self::MAGENTO => MagentoAppType::class,
            self::SKELETON => SkeletonAppType::class,
        };
    }
}
