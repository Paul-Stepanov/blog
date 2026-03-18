<?php

declare(strict_types=1);

namespace App\Domain\Contact\Events;

use App\Domain\Contact\ValueObjects\Email;
use App\Domain\Shared\DomainEvent;
use App\Domain\Shared\Uuid;
use DateTimeImmutable;

/**
 * Contact Message Received Event.
 *
 * Dispatched when a new contact message is submitted.
 */
final class ContactMessageReceived extends DomainEvent
{
    /**
     * @param Uuid $messageId Contact message ID
     * @param string $name Sender name
     * @param Email $email Sender email
     * @param string $subject Message subject
     * @param string $ipAddress Sender IP address
     * @param string|null $userAgent Optional user agent
     */
    public function __construct(
        private Uuid $messageId,
        private string $name,
        private Email $email,
        private string $subject,
        private string $ipAddress,
        private ?string $userAgent,
        DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {
        parent::__construct($occurredAt);
    }

    /**
     * Get contact message ID.
     */
    public function getMessageId(): Uuid
    {
        return $this->messageId;
    }

    /**
     * Get sender name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get sender email.
     */
    public function getEmail(): Email
    {
        return $this->email;
    }

    /**
     * Get message subject.
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Get sender IP address.
     */
    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    /**
     * Get user agent.
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload(): array
    {
        return [
            'message_id' => $this->messageId->getValue(),
            'name' => $this->name,
            'email' => $this->email->getValue(),
            'subject' => $this->subject,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }

    /**
     * {@inheritdoc}
     * @throws \DateMalformedStringException
     */
    public static function fromArray(array $data): self
    {
        return new self(
            messageId: Uuid::fromString($data['message_id']),
            name: $data['name'],
            email: Email::fromString($data['email']),
            subject: $data['subject'],
            ipAddress: $data['ip_address'],
            userAgent: $data['user_agent'] ?? null,
            occurredAt: new DateTimeImmutable($data['occurred_at'])
        );
    }
}