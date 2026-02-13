<?php

declare(strict_types=1);

namespace PhpHive\Cli\Enums;

/**
 * Database Type Enumeration.
 *
 * Defines all supported database types for applications in the PhpHive monorepo.
 * Each database type has different features, performance characteristics, and
 * use cases.
 *
 * Database Selection Considerations:
 * - Performance: MySQL and PostgreSQL offer excellent performance
 * - Features: PostgreSQL has advanced features (JSON, full-text search, etc.)
 * - Simplicity: SQLite is perfect for development and small applications
 * - Compatibility: MySQL/MariaDB have widest framework support
 * - Scalability: PostgreSQL and MySQL scale well for large applications
 *
 * Usage:
 * ```php
 * // Get database identifier
 * $type = DatabaseType::MYSQL->value; // 'mysql'
 *
 * // Get display name
 * $name = DatabaseType::MYSQL->getName(); // 'MySQL'
 *
 * // Get default port
 * $port = DatabaseType::MYSQL->getDefaultPort(); // 3306
 *
 * // Get PDO driver name
 * $driver = DatabaseType::MYSQL->getPdoDriver(); // 'mysql'
 *
 * // Get Docker image
 * $image = DatabaseType::MYSQL->getDockerImage(); // 'mysql:8.0'
 *
 * // Get choices for prompts
 * $choices = DatabaseType::choices();
 * ```
 *
 * @see https://www.php.net/manual/en/book.pdo.php
 */
enum DatabaseType: string
{
    /**
     * MySQL Database.
     *
     * Most popular open-source relational database.
     * Best for: Web applications, e-commerce, content management.
     *
     * Features:
     * - ACID compliance
     * - Replication and clustering
     * - Full-text search
     * - JSON support
     * - Excellent performance
     * - Wide ecosystem support
     *
     * Versions: 8.0+ recommended
     * Default Port: 3306
     * Docker Image: mysql:8.0
     *
     * Compatible with: Laravel, Symfony, Magento, all frameworks
     */
    case MYSQL = 'mysql';

    /**
     * PostgreSQL Database.
     *
     * Advanced open-source relational database with rich features.
     * Best for: Complex queries, data warehousing, geospatial data.
     *
     * Features:
     * - Advanced SQL features
     * - JSONB support (binary JSON)
     * - Full-text search
     * - Array and hstore types
     * - Excellent data integrity
     * - Advanced indexing
     * - Window functions
     * - Common Table Expressions (CTEs)
     *
     * Versions: 14+ recommended
     * Default Port: 5432
     * Docker Image: postgres:16
     *
     * Compatible with: Laravel, Symfony, most frameworks
     * Note: Not supported by Magento
     */
    case POSTGRESQL = 'postgresql';

    /**
     * SQLite Database.
     *
     * Lightweight file-based database, perfect for development.
     * Best for: Development, testing, small applications, embedded systems.
     *
     * Features:
     * - Zero configuration
     * - Single file database
     * - No server required
     * - ACID compliance
     * - Fast for small datasets
     * - Perfect for testing
     *
     * Versions: 3.x
     * Default Port: N/A (file-based)
     * Docker Image: N/A (built into PHP)
     *
     * Compatible with: Laravel, Symfony
     * Note: Not recommended for production, not supported by Magento
     */
    case SQLITE = 'sqlite';

    /**
     * MariaDB Database.
     *
     * MySQL-compatible fork with additional features and performance improvements.
     * Best for: MySQL replacement, high-performance applications.
     *
     * Features:
     * - MySQL compatibility
     * - Better performance than MySQL in some cases
     * - Additional storage engines
     * - Enhanced replication
     * - JSON support
     * - Temporal tables
     *
     * Versions: 10.6+ recommended
     * Default Port: 3306
     * Docker Image: mariadb:10.11
     *
     * Compatible with: Laravel, Symfony, Magento
     * Note: Drop-in replacement for MySQL
     */
    case MARIADB = 'mariadb';

    /**
     * Get choices array for CLI prompts.
     *
     * Returns an associative array suitable for use with Laravel Prompts
     * select() function. Format: ['Display Name (Description)' => 'value']
     *
     * @return array<string, string> Map of display label => value
     */
    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $case) {
            $label = "{$case->getName()} ({$case->getDescription()})";
            $choices[$label] = $case->value;
        }

        return $choices;
    }

    /**
     * Get choices for a specific app type.
     *
     * Returns only the database types compatible with the given app type.
     *
     * @param  AppType               $appType Application type
     * @return array<string, string> Map of display label => value
     */
    public static function choicesForAppType(AppType $appType): array
    {
        $cases = match ($appType) {
            AppType::MAGENTO => [self::MYSQL, self::MARIADB],
            AppType::LARAVEL => [self::MYSQL, self::POSTGRESQL, self::SQLITE],
            AppType::SYMFONY => [self::MYSQL, self::POSTGRESQL, self::SQLITE],
            AppType::SKELETON => [self::MYSQL, self::POSTGRESQL, self::SQLITE],
        };

        $choices = [];
        foreach ($cases as $case) {
            $label = "{$case->getName()} ({$case->getDescription()})";
            $choices[$label] = $case->value;
        }

        return $choices;
    }

    /**
     * Get the display name of the database type.
     *
     * Returns a human-readable name suitable for display in CLI prompts
     * and documentation.
     *
     * @return string Display name (e.g., 'MySQL', 'PostgreSQL')
     */
    public function getName(): string
    {
        return match ($this) {
            self::MYSQL => 'MySQL',
            self::POSTGRESQL => 'PostgreSQL',
            self::SQLITE => 'SQLite',
            self::MARIADB => 'MariaDB',
        };
    }

    /**
     * Get a brief description of the database type.
     *
     * Returns a short description explaining what the database is best for.
     *
     * @return string Brief description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::MYSQL => 'Popular open-source database',
            self::POSTGRESQL => 'Advanced database with rich features',
            self::SQLITE => 'Lightweight file-based database',
            self::MARIADB => 'MySQL-compatible with enhancements',
        };
    }

    /**
     * Get the default port number for this database.
     *
     * Returns the standard port number used by this database type.
     * Returns null for SQLite (file-based, no network port).
     *
     * @return int|null Port number or null for file-based databases
     */
    public function getDefaultPort(): ?int
    {
        return match ($this) {
            self::MYSQL => 3306,
            self::POSTGRESQL => 5432,
            self::SQLITE => null,
            self::MARIADB => 3306,
        };
    }

    /**
     * Get the PDO driver name for this database.
     *
     * Returns the PDO driver identifier used in DSN strings.
     * Used for PHP PDO connections.
     *
     * @return string PDO driver name (e.g., 'mysql', 'pgsql', 'sqlite')
     */
    public function getPdoDriver(): string
    {
        return match ($this) {
            self::MYSQL => 'mysql',
            self::POSTGRESQL => 'pgsql',
            self::SQLITE => 'sqlite',
            self::MARIADB => 'mysql', // MariaDB uses MySQL driver
        };
    }

    /**
     * Get the Laravel database driver name.
     *
     * Returns the driver name used in Laravel's database configuration.
     *
     * @return string Laravel driver name
     */
    public function getLaravelDriver(): string
    {
        return match ($this) {
            self::MYSQL => 'mysql',
            self::POSTGRESQL => 'pgsql',
            self::SQLITE => 'sqlite',
            self::MARIADB => 'mysql',
        };
    }

    /**
     * Get the Symfony Doctrine driver name.
     *
     * Returns the driver name used in Symfony's Doctrine configuration.
     *
     * @return string Doctrine driver name
     */
    public function getDoctrineDriver(): string
    {
        return match ($this) {
            self::MYSQL => 'pdo_mysql',
            self::POSTGRESQL => 'pdo_pgsql',
            self::SQLITE => 'pdo_sqlite',
            self::MARIADB => 'pdo_mysql',
        };
    }

    /**
     * Get the Docker image for this database.
     *
     * Returns the recommended Docker image name and tag for this database type.
     * Returns null for SQLite (no Docker image needed).
     *
     * @return string|null Docker image (e.g., 'mysql:8.0') or null
     */
    public function getDockerImage(): ?string
    {
        return match ($this) {
            self::MYSQL => 'mysql:8.0',
            self::POSTGRESQL => 'postgres:16',
            self::SQLITE => null,
            self::MARIADB => 'mariadb:10.11',
        };
    }

    /**
     * Check if this database requires a server.
     *
     * Returns true if the database requires a server process (MySQL, PostgreSQL, MariaDB).
     * Returns false for file-based databases (SQLite).
     *
     * @return bool True if server required
     */
    public function requiresServer(): bool
    {
        return $this !== self::SQLITE;
    }

    /**
     * Check if this database is compatible with Magento.
     *
     * Magento only supports MySQL and MariaDB.
     *
     * @return bool True if compatible with Magento
     */
    public function isMagentoCompatible(): bool
    {
        return match ($this) {
            self::MYSQL => true,
            self::MARIADB => true,
            self::POSTGRESQL => false,
            self::SQLITE => false,
        };
    }

    /**
     * Get the DATABASE_URL format for Symfony.
     *
     * Returns the DATABASE_URL format string used in Symfony .env files.
     * Placeholders: {user}, {password}, {host}, {port}, {database}
     *
     * @return string DATABASE_URL format
     */
    public function getSymfonyDatabaseUrl(): string
    {
        return match ($this) {
            self::MYSQL => 'mysql://{user}:{password}@{host}:{port}/{database}?serverVersion=8.0',
            self::POSTGRESQL => 'postgresql://{user}:{password}@{host}:{port}/{database}?serverVersion=16&charset=utf8',
            self::SQLITE => 'sqlite:///%kernel.project_dir%/var/data.db',
            self::MARIADB => 'mysql://{user}:{password}@{host}:{port}/{database}?serverVersion=10.11-MariaDB',
        };
    }
}
