<?php

declare(strict_types=1);

namespace PhpHive\Cli\Enums;

use PhpHive\Cli\Contracts\PackageTypeInterface;
use PhpHive\Cli\PackageTypes\LaravelPackageType;
use PhpHive\Cli\PackageTypes\MagentoPackageType;
use PhpHive\Cli\PackageTypes\SkeletonPackageType;
use PhpHive\Cli\PackageTypes\SymfonyPackageType;

/**
 * Package Type Enumeration.
 *
 * Defines all supported package types in the PhpHive monorepo CLI.
 * Each package type represents a different framework-specific package structure
 * that can be created and managed within the monorepo.
 *
 * Package types determine:
 * - Package structure and file organization
 * - Framework-specific files (ServiceProvider, Bundle, Module)
 * - Stub templates used for scaffolding
 * - Composer package configuration
 * - Autoloading configuration
 * - Testing setup
 *
 * Usage:
 * ```php
 * // Get package type identifier
 * $type = PackageType::LARAVEL->value; // 'laravel'
 *
 * // Get display name
 * $name = PackageType::LARAVEL->getDisplayName(); // 'Laravel Package'
 *
 * // Get description
 * $desc = PackageType::LARAVEL->getDescription(); // 'Laravel package with Service Provider'
 *
 * // Check if valid
 * $valid = PackageType::tryFrom('laravel') !== null;
 *
 * // Get all types
 * $all = PackageType::cases();
 *
 * // Get choices for prompts
 * $choices = PackageType::choices();
 * ```
 *
 * Adding a new package type:
 * 1. Add new case to this enum
 * 2. Implement getDisplayName() and getDescription() for the new case
 * 3. Create PackageType class implementing PackageTypeInterface
 * 4. Add to PackageTypeFactory::create()
 * 5. Create stub templates in cli/stubs/packages/{type}/
 *
 * @see PackageTypeInterface
 * @see \PhpHive\Cli\Factories\PackageTypeFactory
 */
enum PackageType: string
{
    /**
     * Laravel Package.
     *
     * Laravel-specific package with Service Provider for integration.
     * Best for: Laravel extensions, reusable components, API clients.
     *
     * Structure:
     * - src/Providers/{Name}ServiceProvider.php - Service provider for registration
     * - src/Http/Controllers/ - HTTP controllers
     * - src/Models/ - Eloquent models
     * - src/Commands/ - Artisan commands
     * - config/{name}.php - Package configuration
     * - routes/web.php, routes/api.php - Package routes
     * - resources/views/ - Blade templates
     * - database/migrations/ - Database migrations
     *
     * Features:
     * - Auto-discovery via composer.json
     * - Configuration publishing
     * - View publishing
     * - Migration publishing
     * - Route registration
     * - Command registration
     *
     * Compatible with: Laravel 10+
     */
    case LARAVEL = 'laravel';

    /**
     * Symfony Bundle.
     *
     * Symfony-specific bundle with Bundle class and DependencyInjection.
     * Best for: Symfony extensions, reusable bundles, integrations.
     *
     * Structure:
     * - src/{Name}Bundle.php - Bundle class
     * - src/DependencyInjection/{Name}Extension.php - Service configuration
     * - src/DependencyInjection/Configuration.php - Configuration tree
     * - src/Controller/ - Controllers
     * - src/Entity/ - Doctrine entities
     * - src/Command/ - Console commands
     * - config/services.yaml - Service definitions
     * - config/routes.yaml - Route configuration
     * - templates/ - Twig templates
     *
     * Features:
     * - Symfony Flex integration
     * - Service container configuration
     * - Configuration validation
     * - Compiler passes
     * - Event subscribers
     * - Twig extensions
     *
     * Compatible with: Symfony 6.4+, 7.x
     */
    case SYMFONY = 'symfony';

    /**
     * Magento Module.
     *
     * Magento 2 module with module.xml and registration.php.
     * Best for: Magento extensions, custom functionality, integrations.
     *
     * Structure:
     * - registration.php - Module registration
     * - src/etc/module.xml - Module declaration
     * - src/etc/di.xml - Dependency injection configuration
     * - src/etc/frontend/routes.xml - Frontend routes
     * - src/etc/adminhtml/routes.xml - Admin routes
     * - src/Controller/ - Controllers
     * - src/Model/ - Models
     * - src/Block/ - Blocks
     * - src/Helper/ - Helper classes
     * - src/view/frontend/layout/ - Frontend layouts
     * - src/view/frontend/templates/ - Frontend templates
     *
     * Features:
     * - Module registration system
     * - Dependency injection
     * - Plugin system (interceptors)
     * - Event/Observer pattern
     * - Layout XML system
     * - Admin configuration
     *
     * Compatible with: Magento 2.4.5+
     */
    case MAGENTO = 'magento';

    /**
     * Skeleton Package.
     *
     * Framework-agnostic PHP library without framework dependencies.
     * Best for: Utility libraries, API clients, shared code, tools.
     *
     * Structure:
     * - src/ - Source code with PSR-4 autoloading
     * - tests/ - PHPUnit tests
     * - composer.json - Composer configuration
     * - README.md - Documentation
     * - LICENSE - License file
     * - .gitignore - Git ignore rules
     *
     * Features:
     * - PSR-4 autoloading
     * - Composer dependency management
     * - PHPUnit testing (optional)
     * - PHPStan static analysis (optional)
     * - PHP CS Fixer code style (optional)
     * - No framework dependencies
     * - Maximum portability
     *
     * Compatible with: Any PHP 8.1+ project
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
     *     'Laravel Package (Laravel package with Service Provider)' => 'laravel',
     *     'Symfony Bundle (Symfony bundle with DependencyInjection)' => 'symfony',
     *     'Magento Module (Magento 2 module with registration)' => 'magento',
     *     'Skeleton Package (Framework-agnostic PHP library)' => 'skeleton',
     * ]
     * ```
     *
     * @return array<string, string> Map of display label => value
     */
    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $case) {
            $label = "{$case->getDisplayName()} ({$case->getDescription()})";
            $choices[$label] = $case->value;
        }

        return $choices;
    }

    /**
     * Get all package type values as an array.
     *
     * Returns a simple array of all package type identifiers.
     * Useful for validation and documentation.
     *
     * Example output: ['laravel', 'symfony', 'magento', 'skeleton']
     *
     * @return array<string> List of package type identifiers
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Get the display name of the package type.
     *
     * Returns a human-readable name suitable for display in CLI prompts,
     * documentation, and user interfaces.
     *
     * @return string Display name (e.g., 'Laravel Package', 'Symfony Bundle')
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::LARAVEL => 'Laravel Package',
            self::SYMFONY => 'Symfony Bundle',
            self::MAGENTO => 'Magento Module',
            self::SKELETON => 'Skeleton Package',
        };
    }

    /**
     * Get a brief description of the package type.
     *
     * Returns a short description explaining what the package type includes.
     * Used in CLI prompts to help users choose the right package type.
     *
     * @return string Brief description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::LARAVEL => 'Laravel package with Service Provider',
            self::SYMFONY => 'Symfony bundle with DependencyInjection',
            self::MAGENTO => 'Magento 2 module with registration',
            self::SKELETON => 'Framework-agnostic PHP library',
        };
    }

    /**
     * Get the fully qualified class name for this package type.
     *
     * Returns the class name of the PackageType implementation that handles
     * scaffolding and configuration for this package type.
     *
     * @return class-string<PackageTypeInterface>
     */
    public function getClassName(): string
    {
        return match ($this) {
            self::LARAVEL => LaravelPackageType::class,
            self::SYMFONY => SymfonyPackageType::class,
            self::MAGENTO => MagentoPackageType::class,
            self::SKELETON => SkeletonPackageType::class,
        };
    }
}
