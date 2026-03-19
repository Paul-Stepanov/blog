<?php

declare(strict_types=1);

namespace App\Application\Contact\DTOs;

use App\Application\Shared\{DTOFormattingTrait, DTOInterface};
use App\Application\Shared\Exceptions\InvalidEntityTypeException;
use App\Domain\Contact\Entities\ContactMessage;
use App\Domain\Shared\Entity;

/**
 * Contact Message Data Transfer Object.
 *
 * Represents a contact form message for API responses.
 */
final readonly class ContactMessageDTO implements DTOInterface
{
    use DTOFormattingTrait;

    /**
     * @param string $id UUID string
     * @param string $name Sender name
     * @param string $email Sender email
     * @param string $subject Message subject
     * @param string $message Message content
     * @param string $ipAddress Sender IP address
     * @param string $userAgent Sender user agent
     * @param bool $isRead Whether message has been read
     * @param string $createdAt ISO 8601 datetime
     * @param string $updatedAt ISO 8601 datetime
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $subject,
        public string $message,
        public string $ipAddress,
        public string $userAgent,
        public bool $isRead,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    /**
     * Create from Domain Entity.
     *
     * @param Entity $entity Domain contact message entity
     */
    public static function fromEntity(Entity $entity): static
    {
        if (!$entity instanceof ContactMessage) {
            throw new InvalidEntityTypeException(
                expectedType: ContactMessage::class,
                actualType: $entity::class
            );
        }

        $timestamps = $entity->getTimestamps();

        return new self(
            id: $entity->getId()->getValue(),
            name: $entity->getName(),
            email: $entity->getEmail()->getValue(),
            subject: $entity->getSubject(),
            message: $entity->getMessage(),
            ipAddress: $entity->getIpAddress()->getValue(),
            userAgent: $entity->getUserAgent(),
            isRead: $entity->isRead(),
            createdAt: self::formatDate($timestamps->getCreatedAt()),
            updatedAt: self::formatDate($timestamps->getUpdatedAt()),
        );
    }

    /**
     * Convert DTO to associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'is_read' => $this->isRead,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Check if message is unread.
     */
    public function isUnread(): bool
    {
        return !$this->isRead;
    }

    /**
     * Get message preview (truncated).
     */
    public function getPreview(int $length = 100): string
    {
        return self::truncateText($this->message, $length);
    }
}