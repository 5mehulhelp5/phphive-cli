<?php

declare(strict_types=1);

namespace PhpHive\Cli\Console\Commands;

use function array_filter;
use function array_map;
use function count;
use function explode;
use function in_array;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

use PhpHive\Cli\Concerns\InteractsWithComposer;
use PhpHive\Cli\Concerns\InteractsWithMonorepo;
use PhpHive\Cli\Concerns\InteractsWithPrompts;
use PhpHive\Cli\Concerns\InteractsWithTurborepo;
use PhpHive\Cli\Support\Container;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base Command Class.
 *
 * This abstract class serves as the foundation for all CLI commands in the monorepo.
 * It extends Symfony Console's Command class and integrates multiple concerns to provide
 * a rich set of functionality for interacting with the monorepo environment.
 *
 * Features:
 * - Composer integration for PHP dependency management
 * - Turborepo integration for task orchestration
 * - Monorepo workspace discovery and management
 * - Laravel Prompts for beautiful interactive CLI prompts
 * - Dependency injection container support
 * - Convenient output helpers and verbosity checks
 *
 * All custom commands should extend this class to inherit these capabilities.
 *
 * Example usage:
 * ```php
 * class InstallCommand extends BaseCommand
 * {
 *     protected function configure(): void
 *     {
 *         $this->setName('install')
 *              ->setDescription('Install dependencies');
 *     }
 *
 *     protected function execute(InputInterface $input, OutputInterface $output): int
 *     {
 *         $this->intro('Installing dependencies...');
 *         $this->turboRun('composer:install');
 *         $this->outro('Installation complete!');
 *         return Command::SUCCESS;
 *     }
 * }
 * ```
 */
abstract class BaseCommand extends Command
{
    use InteractsWithComposer;
    use InteractsWithMonorepo;
    use InteractsWithPrompts;
    use InteractsWithTurborepo;

    /**
     * The input interface for reading command arguments and options.
     *
     * Provides access to user input passed to the command via CLI arguments,
     * options, and interactive prompts.
     */
    protected InputInterface $input;

    /**
     * The output interface for writing messages to the console.
     *
     * Used to display information, warnings, errors, and other messages
     * to the user during command execution.
     */
    protected OutputInterface $output;

    /**
     * The dependency injection container.
     *
     * Provides access to application services and allows for dependency
     * resolution throughout the command lifecycle.
     */
    protected Container $container;

    /**
     * Set the dependency injection container.
     *
     * This method is called by the Application during command registration
     * to inject the container instance. The container provides access to
     * application services and enables dependency resolution.
     *
     * @param Container $container The DI container instance
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * Configure common options available to all commands.
     *
     * This method defines a set of standard options that are available across
     * all commands in the CLI application. These options provide consistent
     * behavior for common operations like workspace targeting, cache control,
     * and interaction modes.
     *
     * Common Options:
     * - --workspace, -w: Target a specific workspace (e.g., demo-app, calculator)
     * - --force, -f: Force operation by ignoring cache
     * - --no-cache: Disable Turbo cache for this run
     * - --no-interaction, -n: Run in non-interactive mode
     * - --all: Apply operation to all workspaces
     * - --dry-run: Preview what would happen without executing
     *
     * Note: Some commands may define additional options like --json or --parallel
     * specific to their functionality. Check individual command help for details.
     *
     * Child commands should call parent::configure() first to inherit these
     * common options, then add their specific options and arguments:
     *
     * Example:
     * ```php
     * protected function configure(): void
     * {
     *     parent::configure(); // Inherit common options
     *
     *     $this->setName('my-command')
     *          ->setDescription('My custom command')
     *          ->addArgument('name', InputArgument::REQUIRED, 'The name');
     * }
     * ```
     */
    protected function configure(): void
    {
        $this->addOption(
            'workspace',
            'w',
            InputOption::VALUE_REQUIRED,
            'Target specific workspace (e.g., demo-app, calculator)',
        );

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force operation by ignoring cache',
        );

        $this->addOption(
            'no-cache',
            null,
            InputOption::VALUE_NONE,
            'Disable Turbo cache for this run',
        );

        $this->addOption(
            'no-interaction',
            'n',
            InputOption::VALUE_NONE,
            'Run in non-interactive mode',
        );

        $this->addOption(
            'all',
            null,
            InputOption::VALUE_NONE,
            'Apply operation to all workspaces',
        );

        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Preview what would happen without executing',
        );
    }

    /**
     * Initialize the command before execution.
     *
     * This method is called by Symfony Console before execute() runs.
     * It stores references to the input and output interfaces for easy
     * access throughout the command lifecycle.
     *
     * Override this method in child classes to perform custom initialization,
     * but always call parent::initialize() to ensure proper setup.
     *
     * @param InputInterface  $input  The input interface
     * @param OutputInterface $output The output interface
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // Store input and output for convenient access in command methods
        $this->input = $input;
        $this->output = $output;

        // Call parent initialization to maintain Symfony Console behavior
        parent::initialize($input, $output);
    }

    /**
     * Check if the command is running in verbose mode.
     *
     * Verbose mode is enabled with the -v option and provides additional
     * output details during command execution. Use this to conditionally
     * display extra information that might be helpful for debugging.
     *
     * @return bool True if verbose mode is enabled, false otherwise
     */
    protected function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    /**
     * Check if the command is running in debug mode.
     *
     * Debug mode is enabled with the -vv or -vvv options and provides
     * the most detailed output. Use this for diagnostic information
     * that's only needed when troubleshooting issues.
     *
     * @return bool True if debug mode is enabled, false otherwise
     */
    protected function isDebug(): bool
    {
        return $this->output->isDebug();
    }

    /**
     * Check if the command is running in quiet mode.
     *
     * Quiet mode is enabled with the -q option and suppresses all output
     * except errors. Use this to check if you should skip informational
     * messages.
     *
     * @return bool True if quiet mode is enabled, false otherwise
     */
    protected function isQuiet(): bool
    {
        return $this->output->isQuiet();
    }

    /**
     * Write a line of text to the console output.
     *
     * This is a convenience method that wraps Symfony's writeln() with
     * optional styling support. The style parameter accepts any valid
     * Symfony Console style tag (info, comment, question, error).
     *
     * Example:
     * ```php
     * $this->line('Processing...', 'info');
     * $this->line('Done!');
     * ```
     *
     * @param string $message The message to display
     * @param string $style   Optional style tag (info, comment, question, error)
     */
    protected function line(string $message, string $style = ''): void
    {
        // Wrap message in style tags if a style is specified
        if ($style !== '' && $style !== '0') {
            $message = "<{$style}>{$message}</{$style}>";
        }

        $this->output->writeln($message);
    }

    /**
     * Write a comment-styled line to the console.
     *
     * Comments are typically displayed in a muted color (gray) and are
     * useful for supplementary information that's less important than
     * primary output.
     *
     * @param string $message The comment message to display
     */
    protected function comment(string $message): void
    {
        $this->line($message, 'comment');
    }

    /**
     * Write a question-styled line to the console.
     *
     * Questions are typically displayed in a distinct color (cyan/blue)
     * and are useful for prompting the user or highlighting interactive
     * elements.
     *
     * @param string $message The question message to display
     */
    protected function question(string $message): void
    {
        $this->line($message, 'question');
    }

    /**
     * Get the value of a command option.
     *
     * Options are passed to commands using the --option-name syntax.
     * This method retrieves the value of a named option, returning
     * null if the option wasn't provided.
     *
     * Example:
     * ```php
     * // Command: ./bin/hive install --force
     * $force = $this->option('force'); // Returns true
     * ```
     *
     * @param  string $name The option name
     * @return mixed  The option value, or null if not set
     */
    protected function option(string $name): mixed
    {
        return $this->input->getOption($name);
    }

    /**
     * Get the value of a command argument.
     *
     * Arguments are positional parameters passed to commands without
     * the -- prefix. This method retrieves the value of a named argument.
     *
     * Example:
     * ```php
     * // Command: ./bin/hive create package calculator
     * $type = $this->argument('type');     // Returns 'package'
     * $name = $this->argument('name');     // Returns 'calculator'
     * ```
     *
     * @param  string $name The argument name
     * @return mixed  The argument value, or null if not set
     */
    protected function argument(string $name): mixed
    {
        return $this->input->getArgument($name);
    }

    /**
     * Check if an option exists and has a truthy value.
     *
     * This is a convenience method that combines checking if an option
     * is defined and if it has a truthy value. Useful for boolean flags.
     *
     * Example:
     * ```php
     * // Command: ./bin/hive install --force
     * if ($this->hasOption('force')) {
     *     // Force flag is present and true
     * }
     * ```
     *
     * @param  string $name The option name to check
     * @return bool   True if the option exists and is truthy, false otherwise
     */
    protected function hasOption(string $name): bool
    {
        // First check if the option is defined in the command
        if (! $this->input->hasOption($name)) {
            return false;
        }

        $optionValue = $this->option($name);

        return in_array($optionValue, [true, '1', 1], true);
    }

    /**
     * Select a single workspace interactively or from option.
     *
     * This method provides a unified way to get a workspace selection from the user.
     * It first checks if a workspace was specified via the --workspace option.
     * If not, it prompts the user to select from available workspaces interactively.
     *
     * The method uses Laravel Prompts for a beautiful interactive selection experience
     * when running in interactive mode. In non-interactive mode, it will return the
     * first available workspace if no workspace option was provided.
     *
     * Example:
     * ```php
     * $workspace = $this->selectWorkspace('Which workspace to install?');
     * $this->info("Installing in {$workspace}...");
     * ```
     *
     * @param  string $prompt The prompt message to display to the user
     * @return string The selected workspace name
     */
    protected function selectWorkspace(string $prompt = 'Select workspace'): string
    {
        // Check if workspace was specified via --workspace option
        $workspace = $this->option('workspace');
        if (is_string($workspace) && $workspace !== '' && $workspace !== '0') {
            return $workspace;
        }

        // Get all available workspaces
        $workspaces = $this->getAllWorkspaces();

        // If no workspaces available, throw an exception
        if ($workspaces === []) {
            throw new RuntimeException('No workspaces found in the monorepo.');
        }

        // If only one workspace exists, return it automatically
        if (count($workspaces) === 1) {
            return $workspaces[0];
        }

        // In non-interactive mode, return the first workspace
        if ($this->option('no-interaction') === true) {
            return $workspaces[0];
        }

        // Use interactive prompt to select workspace
        return (string) $this->select($prompt, $workspaces);
    }

    /**
     * Select multiple workspaces interactively or from option.
     *
     * This method allows users to select one or more workspaces either via
     * the --workspace option (comma-separated) or through an interactive
     * multi-select prompt.
     *
     * The method handles several scenarios:
     * - If --all flag is set, returns all available workspaces
     * - If --workspace option is provided, parses comma-separated values
     * - Otherwise, prompts user for interactive multi-selection
     *
     * Example:
     * ```php
     * $workspaces = $this->selectWorkspaces('Select workspaces to test');
     * foreach ($workspaces as $workspace) {
     *     $this->info("Testing {$workspace}...");
     * }
     * ```
     *
     * @param  string        $prompt The prompt message to display to the user
     * @return array<string> Array of selected workspace names
     */
    protected function selectWorkspaces(string $prompt = 'Select workspaces'): array
    {
        // If --all flag is set, return all workspaces
        if ($this->shouldRunOnAll()) {
            return $this->getAllWorkspaces();
        }

        // Check if workspace(s) were specified via --workspace option
        $workspace = $this->option('workspace');
        if (is_string($workspace) && $workspace !== '' && $workspace !== '0') {
            // Split by comma to support multiple workspaces
            $workspaces = array_map(trim(...), explode(',', $workspace));

            return array_values(array_filter($workspaces, static fn ($w): bool => $w !== '' && $w !== '0'));
        }

        // Get all available workspaces
        $allWorkspaces = $this->getAllWorkspaces();

        // If no workspaces available, throw an exception
        if ($allWorkspaces === []) {
            throw new RuntimeException('No workspaces found in the monorepo.');
        }

        // In non-interactive mode, return all workspaces
        if ($this->option('no-interaction') === true) {
            return $allWorkspaces;
        }

        // Use interactive multi-select prompt
        $selected = $this->multiselect($prompt, $allWorkspaces);

        // Ensure all values are strings
        return array_map(strval(...), $selected);
    }

    /**
     * Get all available workspaces in the monorepo.
     *
     * This method discovers and returns all workspace names defined in the
     * monorepo configuration. It uses the InteractsWithMonorepo trait's
     * getWorkspaces() method to fetch workspace information, then extracts
     * just the workspace names.
     *
     * The returned array contains workspace names as strings, suitable for
     * use in prompts, iteration, or filtering operations.
     *
     * Example:
     * ```php
     * $workspaces = $this->getAllWorkspaces();
     * $this->info('Found ' . count($workspaces) . ' workspaces');
     * ```
     *
     * @return array<string> Array of workspace names
     */
    protected function getAllWorkspaces(): array
    {
        // Get workspace information from monorepo configuration
        $workspaces = $this->getWorkspaces();

        // Extract and return just the workspace names
        return array_map(
            static fn (array $workspace): string => $workspace['name'],
            $workspaces
        );
    }

    /**
     * Check if command should run on all workspaces.
     *
     * This method determines whether the command should be executed across
     * all available workspaces. It returns true in two scenarios:
     * 1. The --all flag is explicitly set
     * 2. No specific workspace was specified via --workspace option
     *
     * This provides a convenient way to implement "run everywhere by default"
     * behavior while still allowing users to target specific workspaces.
     *
     * Example:
     * ```php
     * if ($this->shouldRunOnAll()) {
     *     $workspaces = $this->getAllWorkspaces();
     * } else {
     *     $workspaces = [$this->selectWorkspace()];
     * }
     * ```
     *
     * @return bool True if --all flag is set or no workspace specified
     */
    protected function shouldRunOnAll(): bool
    {
        // Check if --all flag is explicitly set
        if ($this->hasOption('all')) {
            return true;
        }

        // Check if no specific workspace was specified
        $workspace = $this->option('workspace');

        return in_array($workspace, [null, '', '0'], true);
    }

    /**
     * Output data in JSON format.
     *
     * This method formats and outputs data as pretty-printed JSON to the console.
     * It's useful for commands that need to provide machine-readable output for
     * scripting, automation, or integration with other tools.
     *
     * The JSON output is formatted with proper indentation (4 spaces) and includes
     * unescaped slashes and unicode characters for better readability.
     *
     * Example:
     * ```php
     * $data = [
     *     'workspaces' => ['demo-app', 'calculator'],
     *     'status' => 'success',
     * ];
     * $this->outputJson($data);
     * ```
     *
     * @param array<mixed> $data The data to output as JSON
     */
    protected function outputJson(array $data): void
    {
        // Encode data as pretty-printed JSON with readable formatting
        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        // Handle encoding failure
        if ($json === false) {
            throw new RuntimeException('Failed to encode data as JSON');
        }

        // Output the JSON string to console
        $this->output->writeln($json);
    }

    /**
     * Output data as a formatted table.
     *
     * This method creates and displays a formatted table in the console using
     * Symfony Console's Table component. It's ideal for presenting structured
     * data in a human-readable format.
     *
     * The table automatically adjusts column widths based on content and provides
     * a clean, aligned display with borders and separators.
     *
     * Example:
     * ```php
     * $headers = ['Name', 'Type', 'Status'];
     * $rows = [
     *     ['demo-app', 'application', 'active'],
     *     ['calculator', 'package', 'active'],
     * ];
     * $this->outputTable($headers, $rows);
     * ```
     *
     * @param array<string>       $headers Table column headers
     * @param array<array<mixed>> $rows    Table data rows
     */
    protected function outputTable(array $headers, array $rows): void
    {
        // Create a new table instance with the output interface
        $table = new Table($this->output);

        // Configure table with headers and rows
        $table->setHeaders($headers);
        $table->setRows($rows);

        // Render the table to console
        $table->render();
    }
}
