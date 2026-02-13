<?php

declare(strict_types=1);

namespace PhpHive\Cli\Enums;

/**
 * Laravel Starter Kit Types.
 *
 * Defines available authentication scaffolding starter kits for Laravel applications.
 * Starter kits provide pre-built authentication, registration, and profile management.
 *
 * Usage:
 * ```php
 * $kit = LaravelStarterKit::BREEZE->value; // 'breeze'
 * $name = LaravelStarterKit::BREEZE->getName(); // 'Laravel Breeze'
 * $choices = LaravelStarterKit::choices(); // For prompts
 * ```
 */
enum LaravelStarterKit: string
{
    /**
     * No starter kit - Manual authentication setup.
     */
    case NONE = 'none';

    /**
     * Laravel Breeze - Simple, minimal authentication.
     *
     * Features:
     * - Login, registration, password reset
     * - Email verification
     * - Profile management
     * - Blade or Inertia.js (Vue/React)
     * - Tailwind CSS
     * - Lightweight and simple
     */
    case BREEZE = 'breeze';

    /**
     * Laravel Jetstream - Full-featured authentication.
     *
     * Features:
     * - Everything in Breeze plus:
     * - Team management
     * - Two-factor authentication
     * - Session management
     * - API token management
     * - Livewire or Inertia.js
     * - More comprehensive
     */
    case JETSTREAM = 'jetstream';

    /**
     * Get formatted choices for Laravel Prompts select().
     *
     * @return array<string, string> Formatted choices
     */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            if ($case === self::NONE) {
                $choices[$case->value] = $case->getName();
            } else {
                $choices[$case->value] = sprintf(
                    '%s (%s)',
                    $case->getName(),
                    $case->getDescription()
                );
            }
        }

        return $choices;
    }

    /**
     * Get the default starter kit.
     *
     * @return self Default kit
     */
    public static function default(): self
    {
        return self::NONE;
    }

    /**
     * Get all kit values as an array.
     *
     * @return array<string> Array of kit values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the display name for the starter kit.
     *
     * @return string Human-readable kit name
     */
    public function getName(): string
    {
        return match ($this) {
            self::NONE => 'None',
            self::BREEZE => 'Laravel Breeze',
            self::JETSTREAM => 'Laravel Jetstream',
        };
    }

    /**
     * Get the description for the starter kit.
     *
     * @return string Kit description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::NONE => 'No starter kit',
            self::BREEZE => 'Simple authentication',
            self::JETSTREAM => 'Full-featured',
        };
    }
}
