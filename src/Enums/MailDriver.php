<?php

declare(strict_types=1);

namespace PhpHive\Cli\Enums;

/**
 * Mail Driver Enumeration.
 *
 * Defines all supported mail drivers for sending emails.
 *
 * Usage:
 * ```php
 * $driver = MailDriver::SMTP->value; // 'smtp'
 * $name = MailDriver::SMTP->getName(); // 'SMTP'
 * ```
 */
enum MailDriver: string
{
    /**
     * SMTP Mail Driver.
     *
     * Standard email protocol.
     * Best for: Most applications, custom mail servers.
     *
     * Features:
     * - Universal support
     * - Works with any SMTP server
     * - Reliable
     * - Standard protocol
     */
    case SMTP = 'smtp';

    /**
     * Mailgun Mail Driver.
     *
     * Transactional email API service.
     * Best for: High-volume transactional emails.
     *
     * Features:
     * - API-based
     * - Analytics
     * - Webhooks
     * - Email validation
     */
    case MAILGUN = 'mailgun';

    /**
     * Amazon SES Mail Driver.
     *
     * AWS email service.
     * Best for: AWS-hosted applications.
     *
     * Features:
     * - Cost-effective
     * - Scalable
     * - AWS integration
     * - High deliverability
     */
    case SES = 'ses';

    /**
     * Postmark Mail Driver.
     *
     * Transactional email service.
     * Best for: Transactional emails, high deliverability.
     *
     * Features:
     * - Fast delivery
     * - High deliverability
     * - Analytics
     * - Templates
     */
    case POSTMARK = 'postmark';

    /**
     * Sendmail Mail Driver.
     *
     * Local sendmail binary.
     * Best for: Simple setups, local development.
     *
     * Features:
     * - No external service
     * - Simple
     * - Local only
     */
    case SENDMAIL = 'sendmail';

    /**
     * Log Mail Driver.
     *
     * Logs emails instead of sending.
     * Best for: Development, testing.
     *
     * Features:
     * - No actual sending
     * - Useful for testing
     * - Logs to file
     */
    case LOG = 'log';

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
            self::SMTP => 'SMTP',
            self::MAILGUN => 'Mailgun',
            self::SES => 'Amazon SES',
            self::POSTMARK => 'Postmark',
            self::SENDMAIL => 'Sendmail',
            self::LOG => 'Log (Testing)',
        };
    }
}
