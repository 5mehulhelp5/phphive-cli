<?php

declare(strict_types=1);

namespace PhpHive\Cli\Enums;

/**
 * Filesystem Driver Enumeration.
 *
 * Defines all supported filesystem drivers for file storage.
 *
 * Usage:
 * ```php
 * $driver = FilesystemDriver::S3->value; // 's3'
 * $name = FilesystemDriver::S3->getName(); // 'Amazon S3'
 * ```
 */
enum FilesystemDriver: string
{
    /**
     * Local Filesystem Driver.
     *
     * Stores files on local disk.
     * Best for: Development, small applications.
     *
     * Features:
     * - Simple
     * - Fast
     * - No external service
     * - Not scalable
     */
    case LOCAL = 'local';

    /**
     * Public Filesystem Driver.
     *
     * Stores files in public directory.
     * Best for: Publicly accessible files.
     *
     * Features:
     * - Direct URL access
     * - Simple
     * - Local storage
     */
    case PUBLIC = 'public';

    /**
     * Amazon S3 Driver.
     *
     * AWS object storage service.
     * Best for: Production applications, scalable storage.
     *
     * Features:
     * - Highly scalable
     * - Durable
     * - CDN integration
     * - Pay per use
     */
    case S3 = 's3';

    /**
     * MinIO Driver.
     *
     * S3-compatible object storage.
     * Best for: Self-hosted S3 alternative.
     *
     * Features:
     * - S3 compatible
     * - Self-hosted
     * - Open source
     * - Docker support
     */
    case MINIO = 'minio';

    /**
     * FTP Driver.
     *
     * File Transfer Protocol.
     * Best for: Legacy systems, shared hosting.
     *
     * Features:
     * - Universal support
     * - Simple protocol
     * - Not secure (use SFTP)
     */
    case FTP = 'ftp';

    /**
     * SFTP Driver.
     *
     * Secure File Transfer Protocol.
     * Best for: Secure file transfers.
     *
     * Features:
     * - Secure
     * - SSH-based
     * - Encrypted
     */
    case SFTP = 'sftp';

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
            self::LOCAL => 'Local',
            self::PUBLIC => 'Public',
            self::S3 => 'Amazon S3',
            self::MINIO => 'MinIO',
            self::FTP => 'FTP',
            self::SFTP => 'SFTP',
        };
    }
}
