<?php

declare(strict_types=1);

namespace PhpHive\Cli\Factories;

use InvalidArgumentException;
use PhpHive\Cli\Contracts\AppTypeInterface;
use PhpHive\Cli\Enums\AppType;
use PhpHive\Cli\Support\Composer;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Support\Filesystem;
use PhpHive\Cli\Support\Process;

/**
 * App Type Factory.
 *
 * This factory class is responsible for creating and managing different
 * application type instances. It provides a centralized registry of all
 * available app types and handles their instantiation with proper
 * dependency injection.
 *
 * The factory pattern allows:
 * - Centralized management of app types
 * - Easy addition of new app types
 * - Type-safe app type creation
 * - Discovery of available app types
 * - Consistent dependency injection
 *
 * Registered app types:
 * - Laravel: Full-stack PHP framework
 * - Symfony: High-performance PHP framework
 * - Magento: Enterprise e-commerce platform
 * - Skeleton: Minimal PHP application
 *
 * Example usage:
 * ```php
 * $factory = new AppTypeFactory($container);
 *
 * // Get all available app types
 * $types = $factory->getAvailableTypes();
 *
 * // Create a specific app type
 * $laravel = $factory->create('laravel');
 *
 * // Get app type choices for prompts
 * $choices = AppTypeFactory::choices();
 * ```
 *
 * Adding a new app type:
 * 1. Create a new class implementing AppTypeInterface
 * 2. Add it to the AppType enum
 * 3. The factory will automatically handle creation with dependencies
 *
 * @see AppTypeInterface
 */
final readonly class AppTypeFactory
{
    /**
     * Create a new AppTypeFactory instance.
     *
     * @param Container $container The DI container for resolving dependencies
     */
    public function __construct(
        private Container $container,
    ) {}

    /**
     * Get app type choices for interactive prompts.
     *
     * Returns an associative array suitable for use with Laravel Prompts
     * $this->select() function. The array maps display labels to app type identifiers.
     *
     * Format: ['Display Name (Description)' => 'identifier']
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
     * Example usage:
     * ```php
     * $type = $this->select(
     *     label: 'Select application type',
     *     options: AppTypeFactory::choices()
     * );
     * ```
     *
     * @return array<string, string> Map of display label => identifier
     */
    public static function choices(): array
    {
        return AppType::choices();
    }

    /**
     * Get all available app types.
     *
     * Returns an associative array of app type identifiers mapped to their
     * class names. This registry defines all app types that can be created
     * by the factory.
     *
     * The identifier (key) is used:
     * - In command-line arguments
     * - For user selection in prompts
     * - As a unique identifier for the app type
     *
     * The class name (value) must:
     * - Implement AppTypeInterface
     * - Accept Filesystem, Process, and Composer in constructor
     * - Provide getName() and getDescription() methods
     *
     * @return array<string, class-string<AppTypeInterface>> Map of identifier => class name
     */
    public function getAvailableTypes(): array
    {
        $types = [];
        foreach (AppType::cases() as $case) {
            $types[$case->value] = $case->getClassName();
        }

        return $types;
    }

    /**
     * Create an app type instance by identifier.
     *
     * Instantiates and returns an app type object based on the provided
     * identifier. The identifier must match one of the keys in the
     * available types registry.
     *
     * Dependencies are automatically resolved and injected:
     * - Filesystem: From container
     * - Process: Static factory method
     * - Composer: Static factory method
     *
     * Example usage:
     * ```php
     * $laravel = $factory->create('laravel');
     * $config = $laravel->collectConfiguration($input, $output);
     * ```
     *
     * @param  string           $type The app type identifier (e.g., 'laravel', 'symfony')
     * @return AppTypeInterface The instantiated app type object
     *
     * @throws InvalidArgumentException If the app type identifier is not registered
     */
    public function create(string $type): AppTypeInterface
    {
        // Validate and get the enum case
        $appType = AppType::tryFrom($type);

        if ($appType === null) {
            throw new InvalidArgumentException("Unknown app type: {$type}");
        }

        // Get the class name from the enum case
        $className = $appType->getClassName();

        // Resolve dependencies
        $filesystem = $this->container->make(Filesystem::class);
        $process = Process::make();
        $composer = Composer::make();

        // Instantiate and return the app type with dependencies
        return new $className($filesystem, $process, $composer, $this->container);
    }

    /**
     * Check if an app type identifier is valid.
     *
     * Validates whether a given identifier corresponds to a registered
     * app type in the factory.
     *
     * Example usage:
     * ```php
     * if ($factory->isValid('laravel')) {
     *     $app = $factory->create('laravel');
     * }
     * ```
     *
     * @param  string $type The app type identifier to validate
     * @return bool   True if the identifier is valid, false otherwise
     */
    public function isValid(string $type): bool
    {
        return AppType::tryFrom($type) !== null;
    }

    /**
     * Get a list of all app type identifiers.
     *
     * Returns a simple array of all registered app type identifiers.
     * Useful for validation, documentation, or displaying available options.
     *
     * Example output: ['laravel', 'symfony', 'magento', 'skeleton']
     *
     * Example usage:
     * ```php
     * $validTypes = $factory->getIdentifiers();
     * echo "Available types: " . implode(', ', $validTypes);
     * ```
     *
     * @return array<string> List of app type identifiers
     */
    public function getIdentifiers(): array
    {
        return AppType::values();
    }
}
