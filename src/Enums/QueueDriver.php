<?php

declare(strict_types=1);

namespace PhpHive\Cli\Enums;

/**
 * Queue Driver Enumeration.
 *
 * Defines all supported queue drivers for asynchronous job processing.
 *
 * Usage:
 * ```php
 * $driver = QueueDriver::REDIS->value; // 'redis'
 * $name = QueueDriver::REDIS->getName(); // 'Redis'
 * ```
 */
enum QueueDriver: string
{
    /**
     * Synchronous Queue (No Queue).
     *
     * Jobs are executed immediately in the same process.
     * Best for: Development, testing, simple applications.
     */
    case SYNC = 'sync';

    /**
     * Redis Queue Driver.
     *
     * Uses Redis for queue storage.
     * Best for: Most production applications.
     *
     * Features:
     * - Fast and reliable
     * - Simple setup
     * - Good performance
     * - Supports delayed jobs
     */
    case REDIS = 'redis';

    /**
     * Database Queue Driver.
     *
     * Uses database tables for queue storage.
     * Best for: When Redis is not available.
     *
     * Features:
     * - Uses existing database
     * - Persistent
     * - Slower than Redis
     * - Easy to inspect jobs
     */
    case DATABASE = 'database';

    /**
     * RabbitMQ Queue Driver.
     *
     * Full-featured message broker.
     * Best for: Complex messaging patterns, microservices.
     *
     * Features:
     * - Advanced routing
     * - Message acknowledgment
     * - High availability
     * - Multiple protocols
     */
    case RABBITMQ = 'rabbitmq';

    /**
     * Amazon SQS Queue Driver.
     *
     * AWS managed queue service.
     * Best for: AWS-hosted applications.
     *
     * Features:
     * - Fully managed
     * - Scalable
     * - Pay per use
     * - AWS integration
     */
    case SQS = 'sqs';

    /**
     * Beanstalkd Queue Driver.
     *
     * Simple, fast work queue.
     * Best for: Simple queue needs.
     *
     * Features:
     * - Simple protocol
     * - Fast
     * - Lightweight
     * - Job priorities
     */
    case BEANSTALKD = 'beanstalkd';

    /**
     * Get choices for prompts.
     */
    public static function choices(): array
    {
        return [
            'None (Synchronous processing)' => self::SYNC->value,
            'Redis (Lightweight, fast)' => self::REDIS->value,
            'RabbitMQ (Full-featured message broker)' => self::RABBITMQ->value,
            'Amazon SQS (Managed cloud service)' => self::SQS->value,
        ];
    }

    /**
     * Get the display name.
     */
    public function getName(): string
    {
        return match ($this) {
            self::SYNC => 'Sync (No Queue)',
            self::REDIS => 'Redis',
            self::DATABASE => 'Database',
            self::RABBITMQ => 'RabbitMQ',
            self::SQS => 'Amazon SQS',
            self::BEANSTALKD => 'Beanstalkd',
        };
    }
}
