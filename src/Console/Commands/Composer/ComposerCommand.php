<?php

declare(strict_types=1);

namespace PhpHive\Cli\Console\Commands\Composer;

use function implode;

use Override;
use PhpHive\Cli\Console\Commands\BaseCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Composer Command.
 *
 * This command provides direct access to Composer commands within workspace contexts.
 * It acts as a passthrough wrapper that allows running any Composer command in a
 * specific workspace without manually navigating to workspace directories or managing
 * multiple composer.json files across the monorepo.
 *
 * The command forwards all arguments directly to Composer, preserving all flags,
 * options, and behavior of the underlying Composer command. This makes it a flexible
 * tool for any Composer operation that isn't covered by specialized commands like
 * require or update.
 *
 * Features:
 * - Run any Composer command in workspace context
 * - Interactive workspace selection if not specified
 * - Workspace validation before execution
 * - Full argument passthrough to Composer
 * - Support for all Composer flags and options
 * - Real-time command output streaming
 * - Automatic working directory management
 *
 * Common use cases:
 * - Show installed packages (composer show)
 * - Dump autoloader (composer dump-autoload)
 * - Validate composer.json (composer validate)
 * - Check for security issues (composer audit)
 * - Remove packages (composer remove)
 * - Run custom scripts (composer run-script)
 *
 * Workflow:
 * 1. Accepts any Composer command as arguments
 * 2. Selects or validates target workspace
 * 3. Changes to workspace directory
 * 4. Executes Composer with provided arguments
 * 5. Streams output in real-time
 * 6. Reports success or failure
 *
 * Example usage:
 * ```bash
 * # Show installed packages
 * hive composer show --installed
 *
 * # Dump optimized autoloader
 * hive composer dump-autoload -o --workspace=api
 *
 * # Validate composer.json
 * hive composer validate -w calculator
 *
 * # Check for security vulnerabilities
 * hive composer audit
 *
 * # Remove a package
 * hive composer remove symfony/console -w demo-app
 *
 * # Run custom script
 * hive composer run-script post-install
 * ```
 *
 * @see BaseCommand For inherited functionality
 * @see InteractsWithComposer For Composer integration
 * @see InteractsWithMonorepo For workspace discovery
 * @see RequireCommand For adding packages
 * @see UpdateCommand For updating packages
 */
#[AsCommand(
    name: 'composer:run',
    description: 'Run Composer command in a workspace',
    aliases: ['composer', 'comp'],
)]
final class ComposerCommand extends BaseCommand
{
    /**
     * Configure the command options and arguments.
     *
     * Defines the command signature with flexible argument handling to accept
     * any Composer command and its options. The command argument uses IS_ARRAY
     * mode to capture all remaining arguments, allowing full passthrough of
     * Composer commands with their flags and options.
     *
     * Configuration details:
     * - Inherits --workspace (-w) option from BaseCommand
     * - Accepts variadic command arguments (e.g., show --installed)
     * - Preserves all Composer flags and options
     * - Provides comprehensive help text with examples
     *
     * The IS_ARRAY flag allows capturing commands like:
     * - "show --installed" → ['show', '--installed']
     * - "dump-autoload -o" → ['dump-autoload', '-o']
     * - "validate --strict" → ['validate', '--strict']
     */
    #[Override]
    protected function configure(): void
    {
        // Inherit common options from BaseCommand (--workspace, etc.)
        parent::configure();

        $this
            ->addArgument(
                'command',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'The Composer command to run',
            )
            ->setHelp(
                <<<'HELP'
                The <info>composer</info> command runs Composer commands in workspace contexts.

                <comment>Examples:</comment>
                  <info>hive composer require symfony/console</info>
                  <info>hive composer update --workspace=api</info>
                  <info>hive composer show --installed</info>
                  <info>hive composer dump-autoload -o</info>

                If no workspace is specified, you'll be prompted to select one.
                HELP
            );
    }

    /**
     * Execute the composer command.
     *
     * This method orchestrates the Composer command execution by handling workspace
     * selection, validation, and command execution. It acts as a bridge between the
     * CLI interface and the underlying Composer binary, ensuring commands run in the
     * correct workspace context.
     *
     * Execution flow:
     * 1. Display intro banner to user
     * 2. Extract and join command arguments into single string
     * 3. Determine target workspace (from option or interactive selection)
     * 4. Validate workspace exists in monorepo
     * 5. Resolve workspace path for execution
     * 6. Display execution details (command and workspace)
     * 7. Execute Composer command in workspace directory
     * 8. Report success or failure to user
     *
     * Workspace selection logic:
     * - If --workspace option provided: Use specified workspace
     * - If no option: Prompt interactive selection from available workspaces
     * - If no workspaces found: Exit with error
     * - If workspace doesn't exist: Exit with error
     *
     * The command uses the composer() method from InteractsWithComposer trait
     * which handles:
     * - Working directory management (cd to workspace)
     * - Composer binary execution
     * - Real-time output streaming
     * - Exit code propagation
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
        $this->intro('Running Composer command...');

        // =====================================================================
        // EXTRACT COMMAND ARGUMENTS
        // =====================================================================
        // Get the composer command arguments as an array
        // Example: ['show', '--installed'] or ['dump-autoload', '-o']
        $commandArgs = $input->getArgument('command');

        // Join array elements into a single command string
        // Example: ['show', '--installed'] → 'show --installed'
        $composerCommand = implode(' ', $commandArgs);

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
        // This prevents attempting to run commands in non-existent directories
        if (! $this->hasWorkspace($workspace)) {
            $this->error("Workspace '{$workspace}' not found");

            return Command::FAILURE;
        }

        // Get the full filesystem path to the workspace directory
        // This resolves the workspace name to an absolute path
        // Example: 'api' → '/path/to/monorepo/apps/api'
        $workspacePath = $this->getWorkspacePath($workspace);

        // =====================================================================
        // DISPLAY EXECUTION DETAILS
        // =====================================================================
        $this->info("Running: composer {$composerCommand}");
        $this->comment("Workspace: {$workspace}");
        $this->line('');

        // =====================================================================
        // EXECUTE COMPOSER COMMAND
        // =====================================================================
        // Run composer command in workspace directory
        // The composer() method from InteractsWithComposer trait:
        // 1. Changes to the workspace directory
        // 2. Executes: composer {$composerCommand}
        // 3. Streams output in real-time
        // 4. Returns the exit code from Composer
        $exitCode = $this->composer($composerCommand, $workspacePath);

        // =====================================================================
        // REPORT RESULTS
        // =====================================================================
        if ($exitCode === 0) {
            // Success - Composer command completed without errors
            $this->outro('✓ Composer command completed successfully');
        } else {
            // Failure - Composer command failed (dependency conflicts, network issues, etc.)
            $this->error('✗ Composer command failed');
        }

        // Return the exit code from Composer
        // This allows shell scripts to detect success/failure
        return $exitCode;
    }
}
