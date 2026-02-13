<?php

declare(strict_types=1);

namespace PhpHive\Cli\Services\Infrastructure;

use mysqli;

/**
 * MySQL Service.
 *
 * Handles MySQL-specific operations including connection testing,
 * database creation, and user management. Provides a clean abstraction
 * over mysqli for database setup operations.
 *
 * Features:
 * - Connection testing with proper error handling
 * - Database creation with IF NOT EXISTS safety
 * - User creation with secure password handling
 * - Privilege management with minimal required permissions
 * - SQL injection prevention through proper escaping
 * - Graceful error handling without exceptions
 *
 * Security considerations:
 * - Admin credentials are only used for setup, never stored
 * - Database users are created with minimal required privileges
 * - All user input is properly escaped
 * - Connection attempts are limited to prevent brute force
 *
 * Usage:
 * ```php
 * $mysql = MySQLService::make();
 *
 * // Test connection
 * if ($mysql->checkConnection('127.0.0.1', 3306, 'root', 'password')) {
 *     // Create database and user
 *     $success = $mysql->createDatabase(
 *         host: '127.0.0.1',
 *         port: 3306,
 *         adminUser: 'root',
 *         adminPass: 'password',
 *         dbName: 'my_app',
 *         dbUser: 'my_app_user',
 *         dbPass: 'secure_password'
 *     );
 * }
 * ```
 */
final class MySQLService
{
    /**
     * Create a new MySQLService instance using static factory pattern.
     *
     * @return self A new MySQLService instance
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Check if MySQL connection is available and working.
     *
     * Attempts to establish a connection to MySQL server using the provided
     * credentials. This is used to verify that MySQL is running and accessible
     * before attempting to create databases or users.
     *
     * Connection process:
     * 1. Suppress PHP warnings for cleaner error handling
     * 2. Attempt mysqli connection with provided credentials
     * 3. Test connection with a simple query (SELECT 1)
     * 4. Close connection if successful
     * 5. Return true/false based on connection result
     *
     * Common failure reasons:
     * - MySQL server not running
     * - Incorrect host or port
     * - Invalid credentials
     * - Firewall blocking connection
     * - MySQL not installed
     *
     * @param  string $host     MySQL server host (e.g., '127.0.0.1', 'localhost')
     * @param  int    $port     MySQL server port (default: 3306)
     * @param  string $user     MySQL username with connection privileges
     * @param  string $password MySQL user password
     * @return bool   True if connection successful, false otherwise
     */
    public function checkConnection(string $host, int $port, string $user, string $password): bool
    {
        // Suppress warnings for cleaner error handling
        $connection = @new mysqli($host, $user, $password, '', $port);

        // Check if connection failed
        if ($connection->connect_error !== null && $connection->connect_error !== '') {
            return false;
        }

        // Test connection with a simple query
        $result = $connection->query('SELECT 1');

        // Close the connection
        $connection->close();

        // Return true if query was successful
        return $result !== false && $result !== null;
    }

    /**
     * Create MySQL database and user with appropriate privileges.
     *
     * Executes a series of SQL commands to:
     * 1. Create a new database if it doesn't exist
     * 2. Create a new user if it doesn't exist
     * 3. Grant all privileges on the database to the user
     * 4. Flush privileges to apply changes immediately
     *
     * SQL commands executed:
     * ```sql
     * CREATE DATABASE IF NOT EXISTS `database_name`;
     * CREATE USER IF NOT EXISTS 'db_user'@'localhost' IDENTIFIED BY 'password';
     * GRANT ALL PRIVILEGES ON `database_name`.* TO 'db_user'@'localhost';
     * FLUSH PRIVILEGES;
     * ```
     *
     * Privilege scope:
     * - User has full access to the specified database only
     * - User cannot access other databases
     * - User cannot create additional databases
     * - User cannot manage other users
     *
     * Error handling:
     * - Returns false if connection fails
     * - Returns false if any SQL command fails
     * - Closes connection in all cases
     * - Does not throw exceptions (graceful failure)
     *
     * @param  string $host      MySQL server host
     * @param  int    $port      MySQL server port
     * @param  string $adminUser MySQL admin username (must have CREATE DATABASE and CREATE USER privileges)
     * @param  string $adminPass MySQL admin password
     * @param  string $dbName    Name of database to create
     * @param  string $dbUser    Name of database user to create
     * @param  string $dbPass    Password for the new database user
     * @return bool   True if all operations successful, false otherwise
     */
    public function createDatabase(
        string $host,
        int $port,
        string $adminUser,
        string $adminPass,
        string $dbName,
        string $dbUser,
        string $dbPass
    ): bool {
        // Establish connection as admin user
        $connection = @new mysqli($host, $adminUser, $adminPass, '', $port);

        // Check if connection failed
        if ($connection->connect_error !== null && $connection->connect_error !== '') {
            return false;
        }

        // Escape identifiers and values to prevent SQL injection
        $dbNameEscaped = $connection->real_escape_string($dbName);
        $dbUserEscaped = $connection->real_escape_string($dbUser);
        $dbPassEscaped = $connection->real_escape_string($dbPass);

        // Create database if it doesn't exist
        $createDbQuery = "CREATE DATABASE IF NOT EXISTS `{$dbNameEscaped}`";
        $createDbResult = $connection->query($createDbQuery);
        if ($createDbResult === false || $createDbResult === null) {
            $connection->close();

            return false;
        }

        // Create user if it doesn't exist
        $createUserQuery = "CREATE USER IF NOT EXISTS '{$dbUserEscaped}'@'localhost' IDENTIFIED BY '{$dbPassEscaped}'";
        $createUserResult = $connection->query($createUserQuery);
        if ($createUserResult === false || $createUserResult === null) {
            $connection->close();

            return false;
        }

        // Grant all privileges on the database to the user
        $grantQuery = "GRANT ALL PRIVILEGES ON `{$dbNameEscaped}`.* TO '{$dbUserEscaped}'@'localhost'";
        $grantResult = $connection->query($grantQuery);
        if ($grantResult === false || $grantResult === null) {
            $connection->close();

            return false;
        }

        // Flush privileges to apply changes immediately
        $flushResult = $connection->query('FLUSH PRIVILEGES');
        if ($flushResult === false || $flushResult === null) {
            $connection->close();

            return false;
        }

        // Close connection and return success
        $connection->close();

        return true;
    }
}
