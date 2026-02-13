<?php

declare(strict_types=1);

namespace PhpHive\Cli\Enums;

use ValueError;

/**
 * Laravel Octane Server Types.
 *
 * Defines available high-performance application servers for Laravel Octane.
 * Octane supercharges Laravel application performance by serving it using
 * powerful application servers.
 *
 * Usage:
 * ```php
 * $server = OctaneServer::ROADRUNNER->value; // 'roadrunner'
 * $name = OctaneServer::ROADRUNNER->getName(); // 'RoadRunner'
 * $choices = OctaneServer::choices(); // For prompts
 * ```
 */
enum OctaneServer: string
{
    /**
     * RoadRunner - Pure PHP application server.
     *
     * Features:
     * - No PHP extensions required
     * - Easy installation via Composer
     * - HTTP/2 and HTTP/3 support
     * - Built-in worker pool management
     * - Good performance with minimal setup
     */
    case ROADRUNNER = 'roadrunner';

    /**
     * FrankenPHP - Modern PHP application server built on Caddy.
     *
     * Features:
     * - Built on Caddy web server
     * - Automatic HTTPS with Let's Encrypt
     * - HTTP/2 and HTTP/3 support
     * - Early Hints support
     * - Modern architecture
     * - No PHP extensions required
     */
    case FRANKENPHP = 'frankenphp';

    /**
     * Swoole - High-performance PHP extension.
     *
     * Features:
     * - Highest performance option
     * - Coroutine support
     * - Async I/O capabilities
     * - WebSocket support
     * - Requires PECL extension installation
     * - More complex setup
     */
    case SWOOLE = 'swoole';

    /**
     * Get formatted choices for Laravel Prompts select().
     *
     * Returns an associative array with server values as keys and
     * formatted display strings as values.
     *
     * Format: ['value' => 'Name (Description)']
     *
     * @return array<string, string> Formatted choices
     */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->value] = sprintf(
                '%s (%s)',
                $case->getName(),
                $case->getDescription()
            );
        }

        return $choices;
    }

    /**
     * Get the default Octane server.
     *
     * RoadRunner is the default as it requires no PHP extensions
     * and provides good performance with minimal setup.
     *
     * @return self Default server
     */
    public static function default(): self
    {
        return self::ROADRUNNER;
    }

    /**
     * Get all server values as an array.
     *
     * @return array<string> Array of server values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Parse a string value to an OctaneServer enum.
     *
     * @param  string $value Server value
     * @return self   OctaneServer enum case
     *
     * @throws ValueError If value is invalid
     */
    public static function fromString(string $value): self
    {
        return self::from($value);
    }

    /**
     * Get the display name for the Octane server.
     *
     * @return string Human-readable server name
     */
    public function getName(): string
    {
        return match ($this) {
            self::ROADRUNNER => 'RoadRunner',
            self::FRANKENPHP => 'FrankenPHP',
            self::SWOOLE => 'Swoole',
        };
    }

    /**
     * Get the full description for the Octane server.
     *
     * @return string Detailed description with key features
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ROADRUNNER => 'Pure PHP, no extensions required',
            self::FRANKENPHP => 'Modern, built on Caddy',
            self::SWOOLE => 'Requires PHP extension via PECL',
        };
    }

    /**
     * Check if the server requires a PHP extension.
     *
     * @return bool True if PHP extension is required
     */
    public function requiresExtension(): bool
    {
        return match ($this) {
            self::ROADRUNNER => false,
            self::FRANKENPHP => false,
            self::SWOOLE => true,
        };
    }

    /**
     * Get the Composer package name for the server.
     *
     * @return string Composer package name
     */
    public function getComposerPackage(): string
    {
        return match ($this) {
            self::ROADRUNNER => 'spiral/roadrunner-cli',
            self::FRANKENPHP => 'dunglas/frankenphp',
            self::SWOOLE => 'swoole/swoole-src',
        };
    }
}
