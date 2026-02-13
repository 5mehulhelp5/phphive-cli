<?php

declare(strict_types=1);

namespace PhpHive\Cli\Enums;

/**
 * Symfony Project Types.
 *
 * Defines available Symfony project templates with different feature sets.
 *
 * Usage:
 * ```php
 * $type = SymfonyProjectType::WEBAPP->value; // 'webapp'
 * $name = SymfonyProjectType::WEBAPP->getName(); // 'Web Application'
 * $choices = SymfonyProjectType::choices(); // For prompts
 * ```
 */
enum SymfonyProjectType: string
{
    /**
     * Web Application - Full-featured Symfony with Twig, forms, security.
     *
     * Features:
     * - Twig templating engine
     * - Form component
     * - Security component
     * - Doctrine ORM
     * - Asset management
     * - Translation
     * - Mailer
     * - Full-stack framework
     */
    case WEBAPP = 'webapp';

    /**
     * Skeleton - Minimal Symfony for APIs and microservices.
     *
     * Features:
     * - Minimal dependencies
     * - Console component
     * - Routing
     * - HTTP foundation
     * - Ideal for APIs
     * - Lightweight
     * - Add components as needed
     */
    case SKELETON = 'skeleton';

    /**
     * Get formatted choices for Laravel Prompts select().
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
     * Get the default project type.
     *
     * @return self Default type
     */
    public static function default(): self
    {
        return self::WEBAPP;
    }

    /**
     * Get all type values as an array.
     *
     * @return array<string> Array of type values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the display name for the project type.
     *
     * @return string Human-readable type name
     */
    public function getName(): string
    {
        return match ($this) {
            self::WEBAPP => 'Web Application',
            self::SKELETON => 'Microservice/API',
        };
    }

    /**
     * Get the description for the project type.
     *
     * @return string Type description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::WEBAPP => 'Full-featured',
            self::SKELETON => 'Minimal',
        };
    }
}
