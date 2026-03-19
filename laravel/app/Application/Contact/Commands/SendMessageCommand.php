<?php

declare(strict_types=1);

namespace App\Application\Contact\Commands;

use App\Domain\Contact\ValueObjects\{Email, IPAddress};

/**
 * Command to send a contact message.
 *
 * Uses hybrid typing: Email and IPAddress VO for validation,
 * primitives for simple data.
 */
final readonly class SendMessageCommand
{
    /**
     * @param string $name Sender name (primitive - simple text)
     * @param Email $email Sender email (VO - validation)
     * @param string $subject Message subject (primitive - simple text)
     * @param string $message Message content (primitive - validated in Service)
     * @param IPAddress $ipAddress Sender IP (VO - business logic)
     * @param string $userAgent Sender user agent (primitive - metadata)
     */
    public function __construct(
        public string $name,
        public Email $email,
        public string $subject,
        public string $message,
        public IPAddress $ipAddress,
        public string $userAgent,
    ) {}
}