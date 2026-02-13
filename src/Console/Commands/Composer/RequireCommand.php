<?php

declare(strict_types=1);

namespace PhpHive\Cli\Console\Commands\Composer;

use Override;
use PhpHive\Cli\Console\Commands\BaseCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Require Command.
 *
 * This command adds Composer package dependencies to workspaces in the monorepo.
 * It provides a convenient interface for installing packages without manually
 * editing composer.json files or navigating to workspace directories.
 *
 * The command wraps Composer's `require` command and adds monorepo-specific
 * features like workspace selection and validation. It supports both production
 * and development dependencies through the --dev flag.
 *
 * Features:
 * - Interactive workspace selection if not specified
 * - Workspace validation before installation
 * - Support for production and dev dependencies
 * - Version constraint support (e.g., symfony/console:^7.0)
 * - Real-time installation progress
 * - Automatic composer.json updates
 * - Dependency conflict detection
 *
 * Workflow:
 * 1. Validates package name format
 * 2. Selects or validates target workspace
 * 3. Runs composer require in workspace directory
 * 4. Updates composer.lock automatically
 * 5. Reports installation success/failure
 *
 * Example usage:
 * ```bash
 * # Add production dependency
 * hive require symfony/console
 *
 * # Add dev dependency
 * hive require phpunit/phpunit --dev
 *
 * # Add to specific workspace
 * hive require guzzlehttp/guzzle --workspace=api
 *
 * # Add with version constraint
 * hive require symfony/http-client:^7.0 -w api
 * ```
 *
 * @see BaseCommand For inherited functionality
 * @see InteractsWithComposer For Composer integration
 * @see InteractsWithMonorepo For workspace discovery
 * @see UpdateCommand For updating existing packages
 * @see ComposerCommand For direct Composer access
 */
#[AsCommand(
    name: 'composer:require',
    description: 'Add a Composer package to a workspace',
    aliases: ['require', 'req', 'add'],
)]
final class RequireCommand extends BaseCommand
{
    /**
     * Configure the command options and arguments.
     *
     * Defines all command-line options that users can pass to customize
     * the package installation behavior. This method sets up the command
     * signature with required package argument and optional flags.
     *
     * Configuration details:
     * - Inherits --workspace (-w) option from BaseCommand
     * - Requires package argument (vendor/package format)
     * - Adds --dev (-d) flag for development dependencies
     * - Provides comprehensive help text with examples
     *
     * Package argument format:
     * - Simple: "symfony/console" (latest version)
     * - With version: "symfony/console:^7.0" (specific constraint)
     * - With operator: "guzzlehttp/guzzle:>=7.0" (minimum version)
     *
     * The --dev flag determines the dependency type:
     * - Without --dev: Added to "require" section (production)
     * - With --dev: Added to "require-dev" section (development only)
     */
    #[Override]
    protected function configure(): void
    {
        // Inherit common options from BaseCommand (--workspace, etc.)
        parent::configure();

        $this
            ->addArgument(
                'package',
                InputArgument::REQUIRED,
                'The package to require (e.g., symfony/console:^7.0)',
            )
            ->addOption(
                'dev',
                'd',
                InputOption::VALUE_NONE,
                'Add as a development dependency',
            )
            ->setHelp(
                <<<'HELP'
                The <info>require</info> command adds a Composer package to a workspace.

                <comment>Examples:</comment>
                  <info>hive require symfony/console</info>
                  <info>hive require phpunit/phpunit --dev</info>
                  <info>hive require guzzlehttp/guzzle:^7.0 --workspace=api</info>

                If no workspace is specified, you'll be prompted to select one.
                HELP
            );
    }

    /**
     * Execute the require command.
     *
     * This method orchestrates the package installation process by handling
     * workspace selection, dependency type determination, and Composer execution.
     * It ensures packages are added to the correct workspace with proper
     * dependency classification (production vs development).
     *
     * Execution flow:
     * 1. Display intro banner to user
     * 2. Extract package name from arguments
     * 3. Determine dependency type (production or development)
     * 4. Determine target workspace (from option or interactive selection)
     * 5. Validate workspace exists in monorepo
     * 6. Display installation details (package, workspace, type)
     * 7. Execute composer require in workspace directory
     * 8. Report success or failure to user
     *
     * Workspace selection logic:
     * - If --workspace option provided: Use specified workspace
     * - If no option: Prompt interactive selection from available workspaces
     * - If no workspaces found: Exit with error
     * - If workspace doesn't exist: Exit with error
     *
     * Dependency type determination:
     * - Without --dev flag: Production dependency (added to "require")
     * - With --dev flag: Development dependency (added to "require-dev")
     *
     * The command uses the composerRequire() method from InteractsWithComposer
     * trait which handles:
     * - Working directory management (cd to workspace)
     * - Composer require execution with appropriate flags
     * - composer.json automatic updates
     * - Package download and installation
     * - composer.lock updates
     * - Dependency conflict resolution
     * - Real-time output streaming
     * - Exit code propagation
     *
     * Common failure scenarios:
     * - Package not found on Packagist
     * - Version constraint conflicts with existing dependencies
     * - Network connectivity issues
     * - Invalid package name format
     * - Insufficient permissions
     *
     * @param  InputInterface  $input  Command input containing arguments and options
     * @param  OutputInterface $output Command output for displaying messages (unused but required by interface)
     * @return int             Exit code from Composer (0 for success, non-zero for failure)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // =====================================================================
        // DISPLAY INTRO
        // =====================================================================
        $this->intro('Adding Composer package...');

        // =====================================================================
        // EXTRACT PACKAGE AND OPTIONS
        // =====================================================================
        // Extract package name from arguments
        // Format: vendor/package or vendor/package:version
        // Examples: "symfony/console", "guzzlehttp/guzzle:^7.0"
        $package = $input->getArgument('package');

        // Check if this is a dev dependency
        // The --dev flag determines whether the package is added to
        // "require" (production) or "require-dev" (development only)
        $isDev = $input->getOption('dev');

        // =====================================================================
        // WORKSPACE SELECTION
        // =====================================================================
        // Get workspace from --workspace option (may be null or empty)
        $workspace = $input->getOption('workspace');

        if (! is_string($workspace) || $workspace === '') {
            // No workspace specified - prompt user to select one interactively
            // This uses the getWorkspaces() method from InteractsWithMonorepo trait
            // which discovers all workspaces defined in pnpm-workspace.yaml
            $workspaces = $this->getWorkspaces();

            if ($workspaces->isEmpty()) {
                // No workspaces found in monorepo - cannot proceed
                $this->error('No workspaces found');

                return Command::FAILURE;
            }

            // Interactive workspace selection using Laravel Prompts
            // pluck('name') extracts just the workspace names from the collection
            // This creates an array like: ['api', 'web', 'calculator']
            // all() converts the collection to a plain array for the select prompt
            $workspace = $this->select(
                'Select workspace',
                $workspaces->pluck('name')->all(),
            );

            // Ensure workspace is a string after selection
            // The select() method should return a string, but we validate for safety
            if (! is_string($workspace)) {
                $this->error('Invalid workspace selection');

                return Command::FAILURE;
            }
        }

        // =====================================================================
        // WORKSPACE VALIDATION
        // =====================================================================
        // Verify the workspace exists in the monorepo
        // This prevents attempting to install packages in non-existent directories
        if (! $this->hasWorkspace($workspace)) {
            $this->error("Workspace '{$workspace}' not found");

            return Command::FAILURE;
        }

        // =====================================================================
        // DISPLAY INSTALLATION DETAILS
        // =====================================================================
        $this->info("Adding package: {$package}");
        $this->comment("Workspace: {$workspace}");

        // Display dependency type based on --dev flag
        // in_array() with strict comparison checks for truthy values
        // This handles both boolean true and string/int representations
        $this->comment('Type: ' . ((in_array($isDev, [true, '1', 1], true)) ? 'development' : 'production'));
        $this->line('');

        // =====================================================================
        // EXECUTE COMPOSER REQUIRE
        // =====================================================================
        // Run composer require in workspace directory
        // The composerRequire() method from InteractsWithComposer trait:
        // 1. Changes to the workspace directory
        // 2. Executes: composer require {package} [--dev]
        // 3. Updates composer.json with new dependency
        // 4. Downloads and installs the package
        // 5. Updates composer.lock with resolved versions
        // 6. Streams output in real-time
        // 7. Returns the exit code from Composer
        $exitCode = $this->composerRequire($workspace, $package, $isDev);

        // =====================================================================
        // REPORT RESULTS
        // =====================================================================
        if ($exitCode === 0) {
            // Success - package installed and composer.json updated
            $this->outro("✓ Package '{$package}' added successfully");
        } else {
            // Failure - installation failed (see Composer output for details)
            // Common causes: package not found, version conflicts, network issues
            $this->error("✗ Failed to add package '{$package}'");
        }

        // Return the exit code from Composer
        // This allows shell scripts to detect success/failure
        return $exitCode;
    }
}
