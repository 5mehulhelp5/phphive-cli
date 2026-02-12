<?php

declare(strict_types=1);

namespace PhpHive\Cli\Contracts;

/**
 * Package Type Interface.
 *
 * Defines the contract for package type implementations. Each package type
 * (Laravel, Magento, Symfony, Skeleton) implements this interface to provide
 * type-specific behavior for package creation and configuration.
 *
 * Package types handle:
 * - Stub path resolution
 * - Variable preparation for template processing
 * - Post-creation hooks (e.g., composer install)
 * - Type-specific file naming (e.g., ServiceProvider, Bundle)
 */
interface PackageTypeInterface
{
    // =========================================================================
    // STUB VARIABLE CONSTANTS
    // =========================================================================

    /**
     * Package name stub variable.
     *
     * Placeholder for the original package name in stub templates.
     * Example: 'test-laravel'
     */
    public const VAR_PACKAGE_NAME = '{{PACKAGE_NAME}}';

    /**
     * Package namespace stub variable.
     *
     * Placeholder for the PascalCase namespace component in stub templates.
     * Example: 'TestLaravel'
     */
    public const VAR_PACKAGE_NAMESPACE = '{{PACKAGE_NAMESPACE}}';

    /**
     * Composer package name stub variable.
     *
     * Placeholder for the full Composer package name in stub templates.
     * Example: 'phphive/test-laravel'
     */
    public const VAR_COMPOSER_PACKAGE_NAME = '{{COMPOSER_PACKAGE_NAME}}';

    /**
     * Description stub variable.
     *
     * Placeholder for the package description in stub templates.
     */
    public const VAR_DESCRIPTION = '{{DESCRIPTION}}';

    /**
     * Author name stub variable.
     *
     * Placeholder for the package author name in stub templates.
     */
    public const VAR_AUTHOR_NAME = '{{AUTHOR_NAME}}';

    /**
     * Author email stub variable.
     *
     * Placeholder for the package author email in stub templates.
     */
    public const VAR_AUTHOR_EMAIL = '{{AUTHOR_EMAIL}}';

    /**
     * Full namespace stub variable.
     *
     * Placeholder for the full PHP namespace in stub templates.
     * Example: 'PhpHive\TestLaravel'
     */
    public const VAR_NAMESPACE = '{{NAMESPACE}}';

    // =========================================================================
    // PACKAGE TYPE IDENTIFIERS
    // =========================================================================

    /**
     * Laravel package type identifier.
     *
     * Used for Laravel packages with Service Provider and Module support.
     */
    public const TYPE_LARAVEL = 'laravel';

    /**
     * Symfony package type identifier.
     *
     * Used for Symfony bundles with Bundle class and DependencyInjection.
     */
    public const TYPE_SYMFONY = 'symfony';

    /**
     * Magento package type identifier.
     *
     * Used for Magento modules with module.xml and registration.php.
     */
    public const TYPE_MAGENTO = 'magento';

    /**
     * Skeleton package type identifier.
     *
     * Used for generic PHP libraries without framework-specific features.
     */
    public const TYPE_SKELETON = 'skeleton';

    /**
     * Get the package type identifier.
     *
     * @return string Package type (e.g., 'laravel', 'magento', 'symfony', 'skeleton')
     */
    public function getType(): string;

    /**
     * Get the display name for the package type.
     *
     * @return string Human-readable name (e.g., 'Laravel Package', 'Magento Module')
     */
    public function getDisplayName(): string;

    /**
     * Get the description for the package type.
     *
     * @return string Description shown in CLI prompts
     */
    public function getDescription(): string;

    /**
     * Get the stub directory path for this package type.
     *
     * @param  string $stubsBasePath Base path to stubs directory
     * @return string Full path to package type stubs
     */
    public function getStubPath(string $stubsBasePath): string;

    /**
     * Prepare variables for stub template processing.
     *
     * @param  string                $name        Package name
     * @param  string                $description Package description
     * @return array<string, string> Variables for template replacement
     */
    public function prepareVariables(string $name, string $description): array;

    /**
     * Get special file naming rules for this package type.
     *
     * Returns an array of file path patterns and their replacement rules.
     * Used to rename files based on package namespace (e.g., ServiceProvider.php -> TestLaravelServiceProvider.php)
     *
     * @return array<string, string> Map of file patterns to replacement patterns
     */
    public function getFileNamingRules(): array;

    /**
     * Perform post-creation tasks.
     *
     * Called after package files are created. Can be used for:
     * - Running composer install
     * - Generating additional files
     * - Setting up configuration
     *
     * @param string $packagePath Full path to created package
     */
    public function postCreate(string $packagePath): void;
}
