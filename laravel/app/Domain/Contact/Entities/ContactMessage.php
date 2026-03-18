<?php

declare(strict_types=1);

namespace App\Domain\Contact\Entities;

use App\Domain\Contact\ValueObjects\{Email, IPAddress};
use App\Domain\Shared\Entity;
use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\Timestamps;
use App\Domain\Shared\Uuid;

/**
 * ContactMessage Entity.
 *
 * Represents a message sent through the contact form.
 */
final class ContactMessage extends Entity
{
    // Mutable properties
    private bool $isRead;
    private Timestamps $timestamps;

    // Immutable properties (readonly)
    private readonly string $name;
    private readonly Email $email;
    private readonly string $subject;
    private readonly string $message;
    private readonly IPAddress $ipAddress;
    private readonly string $userAgent;

    public function __construct(
        Uuid $id,
        string $name,
        Email $email,
        string $subject,
        string $message,
        IPAddress $ipAddress,
        string $userAgent,
        bool $isRead,
        Timestamps $timestamps,
    ) {
        parent::__construct($id);

        $this->name = $name;
        $this->email = $email;
        $this->subject = $subject;
        $this->message = $message;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->isRead = $isRead;
        $this->timestamps = $timestamps;
    }

    /**
     * Create a new contact message from form submission.
     *
     * @throws ValidationException
     */
    public static function submit(
        Uuid $id,
        string $name,
        Email $email,
        string $subject,
        string $message,
        IPAddress $ipAddress,
        string $userAgent,
    ): self {
        if (empty(trim($name))) {
            throw ValidationException::forField('name', 'Name is required');
        }

        if (empty(trim($message))) {
            throw ValidationException::forField('message', 'Message is required');
        }

        return new self(
            id: $id,
            name: trim($name),
            email: $email,
            subject: trim($subject),
            message: trim($message),
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            isRead: false,
            timestamps: Timestamps::now(),
        );
    }

    /**
     * Reconstruct from persistence.
     */
    public static function reconstitute(
        Uuid $id,
        string $name,
        Email $email,
        string $subject,
        string $message,
        IPAddress $ipAddress,
        string $userAgent,
        bool $isRead,
        Timestamps $timestamps,
    ): self {
        return new self(
            id: $id,
            name: $name,
            email: $email,
            subject: $subject,
            message: $message,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            isRead: $isRead,
            timestamps: $timestamps,
        );
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(): void
    {
        if ($this->isRead) {
            return;
        }

        $this->isRead = true;
        $this->timestamps = $this->timestamps->touch();
    }

    /**
     * Mark message as unread.
     */
    public function markAsUnread(): void
    {
        if (!$this->isRead) {
            return;
        }

        $this->isRead = false;
        $this->timestamps = $this->timestamps->touch();
    }

    /**
     * Check if message is unread.
     */
    public function isUnread(): bool
    {
        return !$this->isRead;
    }

    /**
     * Get message preview (first N characters).
     */
    public function getPreview(int $length = 100): string
    {
        if (strlen($this->message) <= $length) {
            return $this->message;
        }

        return substr($this->message, 0, $length) . '...';
    }

    // Getters

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getIpAddress(): IPAddress
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function getTimestamps(): Timestamps
    {
        return $this->timestamps;
    }
}