<?php

declare(strict_types=1);

namespace PhpHive\Cli\Enums;

/**
 * Cache Driver Enumeration.
 *
 * Defines all supported cache drivers for applications.
 * Cache drivers determine where and how application cache data is stored.
 *
 * Usage:
 * ```php
 * $driver = CacheDriver::REDIS->value; // 'redis'
 * $name = CacheDriver::REDIS->getName(); // 'Redis'
 * ```
 */
enum CacheDriver: string
{
    /**
     * Redis Cache Driver.
     *
     * In-memory data structure store, used as cache and session store.
     * Best for: Production applications, distributed systems.
     *
     * Features:
     * - Very fast (in-memory)
     * - Supports data structures (lists, sets, hashes)
     * - Persistence options
     * - Pub/sub messaging
     * - Atomic operations
     */
    case REDIS = 'redis';

    /**
     * File Cache Driver.
     *
     * Stores cache data in files on disk.
     * Best for: Development, small applications, shared hosting.
     *
     * Features:
     * - No additional services required
     * - Simple setup
     * - Slower than memory-based caches
     * - Not suitable for distributed systems
     */
    case FILE = 'file';

    /**
     * Array Cache Driver.
     *
     * Stores cache in memory for current request only.
     * Best for: Testing, development.
     *
     * Features:
     * - Fastest (no I/O)
     * - Data lost after request
     * - Not persistent
     * - Not shared between requests
     */
    case ARRAY = 'array';

    /**
     * Database Cache Driver.
     *
     * Stores cache data in database tables.
     * Best for: When Redis is not available.
     *
     * Features:
     * - Uses existing database
     * - Persistent
     * - Slower than Redis
     * - Can be shared across servers
     */
    case DATABASE = 'database';

    /**
     * Memcached Cache Driver.
     *
     * Distributed memory caching system.
     * Best for: High-traffic applications, distributed caching.
     *
     * Features:
     * - Very fast (in-memory)
     * - Distributed caching
     * - Simple key-value store
     * - No persistence
     */
    case MEMCACHED = 'memcached';

    /**
     * DynamoDB Cache Driver.
     *
     * AWS DynamoDB as cache backend.
     * Best for: AWS-hosted applications.
     *
     * Features:
     * - Fully managed
     * - Scalable
     * - Persistent
     * - AWS integration
     */
    case DYNAMODB = 'dynamodb';

    /**
     * Get choices for prompts.
     */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->getName()] = $case->value;
        }

        return $choices;
    }

    /**
     * Get the display name.
     */
    public function getName(): string
    {
        return match ($this) {
            self::REDIS => 'Redis',
            self::FILE => 'File',
            self::ARRAY => 'Array',
            self::DATABASE => 'Database',
            self::MEMCACHED => 'Memcached',
            self::DYNAMODB => 'DynamoDB',
        };
    }
}
