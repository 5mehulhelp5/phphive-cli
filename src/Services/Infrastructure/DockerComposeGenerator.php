<?php

declare(strict_types=1);

namespace PhpHive\Cli\Services\Infrastructure;

use PhpHive\Cli\Enums\DatabaseType;
use Pixielity\StubGenerator\Exceptions\StubNotFoundException;
use Pixielity\StubGenerator\Facades\Stub;

/**
 * Docker Compose Generator Service.
 *
 * Generates docker-compose.yml files from templates for different database
 * types. Handles template loading, variable replacement, and file generation.
 *
 * Features:
 * - Template-based generation from stubs
 * - Support for multiple database types (MySQL, PostgreSQL, MariaDB)
 * - Variable replacement for configuration
 * - Optional service sections (phpMyAdmin, Adminer)
 * - Proper error handling
 *
 * Template structure:
 * - Located in cli/stubs/docker/
 * - Separate templates for each database type
 * - Support for removable sections (admin tools)
 *
 * Usage:
 * ```php
 * $generator = DockerComposeGenerator::make();
 *
 * $success = $generator->generate(
 *     type: DatabaseType::MYSQL,
 *     appPath: '/path/to/app',
 *     variables: [
 *         'container_prefix' => 'phphive-myapp',
 *         'db_name' => 'myapp',
 *         'db_user' => 'myapp_user',
 *         'db_password' => 'password',
 *         // ... other variables
 *     ]
 * );
 * ```
 */
final class DockerComposeGenerator
{
    /**
     * Create a new DockerComposeGenerator instance using static factory pattern.
     *
     * @return self A new DockerComposeGenerator instance
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Generate docker-compose.yml file from template.
     *
     * Reads the appropriate template file based on database type,
     * replaces placeholders with actual values, and writes the
     * docker-compose.yml file to the application directory.
     *
     * Template placeholders:
     * - container_prefix: phphive-{app-name}
     * - volume_prefix: phphive-{app-name}
     * - network_name: phphive-{app-name}
     * - db_name: Database name
     * - db_user: Database username
     * - db_password: Database password
     * - db_root_password: Root/admin password
     * - db_port: Database port (3306, 5432)
     * - phpmyadmin_port: phpMyAdmin port (8080)
     * - adminer_port: Adminer port (8080)
     * - redis_port: Redis port (6379)
     * - elasticsearch_port: Elasticsearch port (9200)
     *
     * @param  DatabaseType         $databaseType Database type enum
     * @param  string               $appPath      Application directory path
     * @param  array<string, mixed> $variables    Template variables for replacement
     * @return bool                 True on success, false on failure
     */
    public function generate(DatabaseType $databaseType, string $appPath, array $variables): bool
    {
        try {
            // Set base path for stubs
            Stub::setBasePath(dirname(__DIR__, 3) . '/stubs');

            // Determine template file based on database type
            $templateFile = match ($databaseType) {
                DatabaseType::POSTGRESQL => 'docker/postgresql.yml',
                DatabaseType::MARIADB => 'docker/mariadb.yml',
                default => 'docker/mysql.yml',
            };

            // Create stub with replacements
            $stub = Stub::create($templateFile, $variables);

            // Remove admin service if not wanted
            if (isset($variables['include_admin']) && $variables['include_admin'] === false) {
                $stub->removeSection('phpmyadmin')
                    ->removeSection('adminer');
            }

            // Save to application directory
            $stub->saveTo($appPath, 'docker-compose.yml');

            return true;
        } catch (StubNotFoundException) {
            return false;
        }
    }
}
