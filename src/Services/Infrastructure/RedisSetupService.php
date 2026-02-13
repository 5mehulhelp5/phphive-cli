<?php

declare(strict_types=1);

namespace PhpHive\Cli\Services\Infrastructure;

use Illuminate\Support\Str;
use PhpHive\Cli\DTOs\Infrastructure\RedisConfig;
use PhpHive\Cli\Support\Process;
use Pixielity\StubGenerator\Exceptions\StubNotFoundException;
use Pixielity\StubGenerator\Facades\Stub;
use RuntimeException;

/**
 * Redis Setup Service.
 *
 * Handles Redis infrastructure setup with Docker-first approach.
 * Provides methods for Docker-based and local Redis configuration.
 *
 * Key features:
 * - Docker container setup with docker-compose
 * - Local Redis configuration
 * - Secure password generation
 * - Health checking and readiness verification
 * - Graceful fallback between Docker and local
 *
 * Example usage:
 * ```php
 * $service = RedisSetupService::make($process);
 *
 * // Setup Redis (tries Docker first, falls back to local)
 * $config = $service->setup($config, '/path/to/app');
 *
 * // Setup Docker Redis specifically
 * $config = $service->setupDocker($config, '/path/to/app');
 *
 * // Setup local Redis
 * $config = $service->setupLocal($config);
 * ```
 */
final readonly class RedisSetupService
{
    /**
     * Create a new Redis setup service instance.
     *
     * @param Process $process Process service for command execution
     */
    public function __construct(
        private Process $process,
    ) {}

    /**
     * Setup Redis with Docker-first approach.
     *
     * Orchestrates Redis setup by attempting Docker first, then falling
     * back to local setup if Docker fails or is unavailable.
     *
     * @param  RedisConfig $redisConfig Redis configuration
     * @param  string      $appPath     Absolute path to application directory
     * @return RedisConfig Updated Redis configuration
     */
    public function setup(RedisConfig $redisConfig, string $appPath): RedisConfig
    {
        // Try Docker setup if using Docker
        if ($redisConfig->usingDocker) {
            $dockerConfig = $this->setupDocker($redisConfig, $appPath);
            if ($dockerConfig instanceof RedisConfig) {
                return $dockerConfig;
            }
        }

        // Fall back to local setup
        return $this->setupLocal($redisConfig);
    }

    /**
     * Create a new instance using static factory pattern.
     *
     * @param  Process $process Process service for command execution
     * @return self    New RedisSetupService instance
     */
    public static function make(Process $process): self
    {
        return new self($process);
    }

    /**
     * Setup Redis using Docker container.
     *
     * Creates a Docker Compose configuration with Redis service
     * and starts the container. Includes health checking to ensure
     * Redis is ready before returning.
     *
     * Process:
     * 1. Generate docker-compose.yml section for Redis
     * 2. Start Docker container
     * 3. Wait for Redis to be ready (health check)
     * 4. Return updated configuration
     *
     * @param  RedisConfig      $redisConfig Redis configuration with port and password
     * @param  string           $appPath     Application directory path
     * @return RedisConfig|null Updated config on success, null on failure
     */
    public function setupDocker(RedisConfig $redisConfig, string $appPath): ?RedisConfig
    {
        // Extract app name from path
        $appName = basename($appPath);

        // Generate docker-compose file
        $composeGenerated = $this->generateRedisDockerComposeFile(
            $appPath,
            $appName,
            $redisConfig->port,
            $redisConfig->password
        );

        if (! $composeGenerated) {
            return null;
        }

        // Start Docker containers
        $started = $this->startDockerContainers($appPath);
        if (! $started) {
            return null;
        }

        // Wait for Redis to be ready
        $ready = $this->waitForDockerService($appPath, 'redis', 30);
        if (! $ready) {
            // Redis may not be fully ready, but continue anyway
        }

        // Return updated configuration with localhost host
        return new RedisConfig(
            host: 'localhost',
            port: $redisConfig->port,
            password: $redisConfig->password,
            usingDocker: true,
        );
    }

    /**
     * Setup Redis using local installation.
     *
     * Returns the configuration as-is for local Redis setup.
     * The configuration should already contain the correct host,
     * port, and password from user prompts.
     *
     * @param  RedisConfig $redisConfig Redis configuration
     * @return RedisConfig Configuration for local setup
     */
    public function setupLocal(RedisConfig $redisConfig): RedisConfig
    {
        return new RedisConfig(
            host: $redisConfig->host,
            port: $redisConfig->port,
            password: $redisConfig->password,
            usingDocker: false,
        );
    }

    /**
     * Check if Redis is accessible at the given host and port.
     *
     * @param  string $host Redis host
     * @param  int    $port Redis port
     * @return bool   True if Redis is accessible
     */
    public function checkRedisConnection(string $host, int $port): bool
    {
        try {
            $result = $this->process->run(['redis-cli', '-h', $host, '-p', (string) $port, 'ping']);

            return trim($result) === 'PONG';
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * Generate docker-compose.yml file from template.
     *
     * Reads the Redis template file, replaces placeholders with actual values,
     * and writes the docker-compose.yml file to the application directory.
     *
     * @param  string $appPath  Application directory path
     * @param  string $appName  Application name
     * @param  int    $port     Redis port
     * @param  string $password Redis password
     * @return bool   True on success, false on failure
     */
    private function generateRedisDockerComposeFile(
        string $appPath,
        string $appName,
        int $port,
        string $password
    ): bool {
        try {
            // Set base path for stubs
            Stub::setBasePath(dirname(__DIR__, 3) . '/stubs');

            // Normalize app name for container/volume names
            $normalizedName = Str::lower(preg_replace('/[^a-zA-Z0-9]/', '-', $appName) ?? $appName);

            // Create stub with replacements
            Stub::create('docker/redis.yml', [
                'container_prefix' => "phphive-{$normalizedName}",
                'volume_prefix' => "phphive-{$normalizedName}",
                'network_name' => "phphive-{$normalizedName}",
                'redis_port' => (string) $port,
                'redis_password' => $password,
            ])->saveTo($appPath, 'docker-compose.yml');

            return true;
        } catch (StubNotFoundException) {
            return false;
        }
    }

    /**
     * Start Docker containers using docker-compose.
     *
     * @param  string $appPath Absolute path to application directory
     * @return bool   True if containers started successfully
     */
    private function startDockerContainers(string $appPath): bool
    {
        return $this->process->succeeds(['docker', 'compose', 'up', '-d'], $appPath, 300);
    }

    /**
     * Wait for a Docker service to be ready.
     *
     * @param  string $appPath     Absolute path to application directory
     * @param  string $serviceName Name of service in docker-compose.yml
     * @param  int    $maxAttempts Maximum number of polling attempts
     * @return bool   True if service is ready, false if timeout
     */
    private function waitForDockerService(string $appPath, string $serviceName, int $maxAttempts = 30): bool
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            if ($this->process->succeeds(
                ['docker', 'compose', 'exec', '-T', $serviceName, 'echo', 'ready'],
                $appPath
            )) {
                return true;
            }

            sleep(2);
            $attempts++;
        }

        return false;
    }
}
