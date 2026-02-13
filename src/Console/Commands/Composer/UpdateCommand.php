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
 * Update Command.
 *
 * This command updates Composer dependencies in workspaces within the monorepo.
 * It provides a convenient interface for keeping packages up-to-date without
 * manually navigating to workspace directories or running Composer commands
 * directly. The command supports both full dependency updates and targeted
 * single-package updates.
 *
 * The update process follows Composer's standard update workflow:
 * 1. Reads composer.json to determine current constraints
 * 2. Checks for newer versions matching constraints
 * 3. Resolves dependency tree with new versions
 * 4. Downloads and installs updated packages
 * 5. Updates composer.lock with new versions
 *
 * Features:
 * - Update all dependencies in a workspace
 * - Update specific package only
 * - Interactive workspace selection if not specified
 * - Workspace validation before update
 * - Respects version constraints in composer.json
 * - Automatic composer.lock updates
 * - Real-time update progress
 * - Dependency conflict detection
 *
 * Update strategies:
 * - Full update: Updates all packages to latest allowed versions
 * - Targeted update: Updates only specified package and its dependencies
 * - Respects semantic versioning constraints (^, ~, etc.)
 * - Maintains compatibility with other packages
 *
 * Workflow:
 * 1. Selects or validates target workspace
 * 2. Determines update scope (all or specific package)
 * 3. Runs composer update in workspace directory
 * 4. Resolves and installs updated dependencies
 * 5. Updates composer.lock file
 * 6. Reports update success or failure
 *
 * Example usage:
 * ```bash
 * # Update all dependencies in selected workspace
 * hive update
 *
 * # Update all dependencies in specific workspace
 * hive update --workspace=api
 *
 * # Update specific package only
 * hive update symfony/console
 *
 * # Update specific package in specific workspace
 * hive update guzzlehttp/guzzle -w api
 *
 * # Using aliases
 * hive up symfony/http-client -w demo-app
 * hive upgrade --workspace=calculator
 * ```
 *
 * @see BaseCommand For inherited functionality
 * @see InteractsWithComposer For Composer integration
 * @see InteractsWithMonorepo For workspace discovery
 * @see RequireCommand For adding new packages
 * @see ComposerCommand For direct Composer access
 */
#[AsCommand(
    name: 'composer:update',
    description: 'Update Composer dependencies in a workspace',
    aliases: ['update', 'up', 'upgrade'],
)]
final class UpdateCommand extends BaseCommand
{
    /**
     * Configure the command options and arguments.
     *
     * Defines the command signature with optional package argument for targeted
     * updates and output format options. This method sets up flexible command
     * behavior that supports both full and targeted updates with multiple
     * output formats.
     *
     * Configuration details:
     * - Inherits --workspace (-w) option from BaseCommand
     * - Accepts optional package argument for targeted updates
     * - Adds --json (-j) flag for machine-readable output
     * - Adds --summary (-s) flag for table-formatted output
     * - Provides comprehensive help text with examples
     *
     * Update scope:
     * - Without package argument: Updates all dependencies
     * - With package argument: Updates only specified package
     *
     * Output formats:
     * - Default: Human-readable with intro/outro messages
     * - JSON (--json): Machine-readable for CI/CD integration
     * - Summary (--summary): Table format with key metrics
     *
     * The optional package argument allows users to:
     * - Update all dependencies: hive update
     * - Update specific package: hive update symfony/console
     */
    #[Override]
    protected function configure(): void
    {
        // Inherit common options from BaseCommand (--workspace, etc.)
        parent::configure();

        $this
            ->addArgument(
                'package',
                InputArgument::OPTIONAL,
                'Specific package to update (optional)',
            )
            ->addOption(
                'json',
                'j',
                InputOption::VALUE_NONE,
                'Output as JSON with update summary',
            )
            ->addOption(
                'summary',
                's',
                InputOption::VALUE_NONE,
                'Output table view of updates',
            )
            ->setHelp(
                <<<'HELP'
                The <info>update</info> command updates Composer dependencies.

                <comment>Examples:</comment>
                  <info>hive update</info>                    Update all dependencies
                  <info>hive update symfony/console</info>    Update specific package
                  <info>hive update --workspace=api</info>    Update in specific workspace

                If no workspace is specified, you'll be prompted to select one.
                HELP
            );
    }

    /**
     * Execute the update command.
     *
     * This method orchestrates the dependency update process by handling workspace
     * selection, update scope determination (full or targeted), output format
     * selection, and Composer execution. It supports multiple output formats for
     * different use cases (interactive, CI/CD, reporting).
     *
     * Execution flow:
     * 1. Extract output format options (JSON, summary, or default)
     * 2. Start timing for duration calculation
     * 3. Display intro banner (unless structured output)
     * 4. Extract package name if specified (optional)
     * 5. Determine target workspace (from option or interactive selection)
     * 6. Validate workspace exists in monorepo
     * 7. Display update details (full or targeted)
     * 8. Execute composer update in workspace directory
     * 9. Calculate execution duration
     * 10. Report results in requested format
     *
     * Workspace selection logic:
     * - If --workspace option provided: Use specified workspace
     * - If no option and interactive mode: Prompt selection from available workspaces
     * - If no option and structured output (--json/--summary): Exit with error
     * - If no workspaces found: Exit with error
     * - If workspace doesn't exist: Exit with error
     *
     * Update scope determination:
     * - If package argument provided: Targeted update (single package + dependencies)
     * - If no package argument: Full update (all dependencies)
     *
     * Output format options:
     * - Default: Human-readable with intro/outro messages
     * - JSON (--json): Machine-readable for CI/CD integration
     * - Summary (--summary): Table format with key metrics
     *
     * The command uses the composerUpdate() method from InteractsWithComposer
     * trait which handles:
     * - Working directory management (cd to workspace)
     * - Composer update execution with appropriate arguments
     * - Version constraint resolution from composer.json
     * - Package download and installation
     * - composer.lock updates with new versions
     * - Dependency conflict resolution
     * - Real-time output streaming
     * - Exit code propagation
     *
     * Update behavior:
     * - Respects version constraints in composer.json (^, ~, *, etc.)
     * - Updates to latest version within constraints
     * - Resolves entire dependency tree for compatibility
     * - Updates composer.lock with resolved versions
     * - Downloads and installs updated packages
     *
     * Common failure scenarios:
     * - Version constraint conflicts between dependencies
     * - Network connectivity issues
     * - Insufficient permissions
     * - Corrupted composer.lock file
     * - Platform requirement mismatches (PHP version, extensions)
     *
     * @param  InputInterface  $input  Command input containing arguments and options
     * @param  OutputInterface $output Command output for displaying messages (unused but required by interface)
     * @return int             Exit code from Composer (0 for success, non-zero for failure)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // =====================================================================
        // EXTRACT OUTPUT FORMAT OPTIONS
        // =====================================================================
        // Determine which output format the user requested
        $jsonOutput = $this->hasOption('json');
        $summaryOutput = $this->hasOption('summary');

        // =====================================================================
        // START TIMING
        // =====================================================================
        // Track start time for duration calculation
        // microtime(true) returns timestamp with microsecond precision
        $startTime = microtime(true);

        // =====================================================================
        // DISPLAY INTRO
        // =====================================================================
        // Display intro banner (skip for structured output formats)
        // JSON and summary outputs don't need decorative messages
        if (! $jsonOutput && ! $summaryOutput) {
            $this->intro('Updating Composer dependencies...');
        }

        // =====================================================================
        // EXTRACT PACKAGE ARGUMENT
        // =====================================================================
        // Extract package name from arguments (optional)
        // If provided: targeted update (only this package)
        // If null: full update (all dependencies)
        $package = $input->getArgument('package');

        // =====================================================================
        // WORKSPACE SELECTION
        // =====================================================================
        // Get workspace from --workspace option (may be null or empty)
        $workspace = $input->getOption('workspace');

        if (! is_string($workspace) || $workspace === '') {
            // No workspace specified - handle based on output mode
            if ($jsonOutput || $summaryOutput) {
                // Structured output modes require explicit workspace
                // Interactive prompts don't work in CI/CD or scripted contexts
                $this->error('Workspace must be specified when using --json or --summary flags');

                return Command::FAILURE;
            }

            // Interactive mode - prompt user to select workspace
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
        // This prevents attempting to update dependencies in non-existent directories
        if (! $this->hasWorkspace($workspace)) {
            if ($jsonOutput) {
                // JSON output for CI/CD integration
                $this->outputJson([
                    'status' => 'error',
                    'message' => "Workspace '{$workspace}' not found",
                    'workspace' => $workspace,
                    'timestamp' => date('c'),
                ]);
            } else {
                // Human-readable error message
                $this->error("Workspace '{$workspace}' not found");
            }

            return Command::FAILURE;
        }

        // =====================================================================
        // DISPLAY UPDATE DETAILS
        // =====================================================================
        // Display update details (skip for structured output formats)
        if (! $jsonOutput && ! $summaryOutput) {
            if (is_string($package) && $package !== '') {
                // Targeted update - specific package only
                // This updates the specified package and its dependencies
                $this->info("Updating package: {$package}");
            } else {
                // Full update - all dependencies
                // This updates all packages in composer.json to latest allowed versions
                $this->info('Updating all dependencies');
            }

            $this->comment("Workspace: {$workspace}");
            $this->line('');
        }

        // =====================================================================
        // EXECUTE COMPOSER UPDATE
        // =====================================================================
        // Run composer update in workspace directory
        // The composerUpdate() method from InteractsWithComposer trait:
        // 1. Changes to the workspace directory
        // 2. Executes: composer update [package]
        // 3. Reads composer.json for version constraints
        // 4. Resolves dependency tree with new versions
        // 5. Downloads and installs updated packages
        // 6. Updates composer.lock with resolved versions
        // 7. Streams output in real-time
        // 8. Returns the exit code from Composer
        $exitCode = $this->composerUpdate($workspace, $package);

        // =====================================================================
        // CALCULATE DURATION
        // =====================================================================
        // Calculate execution duration in seconds
        // round() to 2 decimal places for readability
        $duration = round(microtime(true) - $startTime, 2);

        // =====================================================================
        // PREPARE RESULT DATA
        // =====================================================================
        // Prepare result data for output formatting
        $success = $exitCode === 0;
        $status = $success ? 'success' : 'failed';

        // =====================================================================
        // HANDLE JSON OUTPUT
        // =====================================================================
        // Handle JSON output for CI/CD integration
        if ($jsonOutput) {
            $this->outputJson([
                'status' => $status,
                'workspace' => $workspace,
                'package' => $package ?? 'all',
                'update_type' => $package !== null ? 'targeted' : 'full',
                'duration_seconds' => $duration,
                'exit_code' => $exitCode,
                'timestamp' => date('c'),
            ]);

            return $exitCode;
        }

        // =====================================================================
        // HANDLE SUMMARY OUTPUT
        // =====================================================================
        // Handle summary output in table format
        if ($summaryOutput) {
            $this->table(
                ['Property', 'Value'],
                [
                    ['Status', $success ? '✓ Success' : '✗ Failed'],
                    ['Workspace', $workspace],
                    ['Package', $package ?? 'all dependencies'],
                    ['Type', $package !== null ? 'Targeted' : 'Full update'],
                    ['Duration', "{$duration}s"],
                ]
            );

            return $exitCode;
        }

        // =====================================================================
        // HANDLE DEFAULT OUTPUT
        // =====================================================================
        // Default output (human-readable with decorative messages)
        if ($success) {
            if (is_string($package) && $package !== '') {
                // Targeted update success
                $this->outro("✓ Package '{$package}' updated successfully");
            } else {
                // Full update success
                $this->outro('✓ Dependencies updated successfully');
            }
        } else {
            // Update failed - see Composer output for details
            $this->error('✗ Update failed');
        }

        // Return the exit code from Composer
        // This allows shell scripts to detect success/failure
        return $exitCode;
    }
}
