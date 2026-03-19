<?php

declare(strict_types=1);

namespace App\Application\User\DTOs;

use App\Application\Shared\{DTOFormattingTrait, DTOInterface};
use App\Application\Shared\Exceptions\InvalidEntityTypeException;
use App\Domain\Shared\Entity;
use App\Domain\User\Entities\User;

/**
 * User Data Transfer Object.
 *
 * Represents a user for API responses (excludes sensitive data like password).
 */
final readonly class UserDTO implements DTOInterface
{
    use DTOFormattingTrait;

    /**
     * @param string $id UUID string
     * @param string $name User display name
     * @param string $email User email
     * @param string $role User role (admin, editor, author)
     * @param string $createdAt ISO 8601 datetime
     * @param string $updatedAt ISO 8601 datetime
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $role,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    /**
     * Create from Domain Entity.
     *
     * @param Entity $entity Domain user entity
     */
    public static function fromEntity(Entity $entity): static
    {
        if (!$entity instanceof User) {
            throw new InvalidEntityTypeException(
                expectedType: User::class,
                actualType: $entity::class
            );
        }

        $timestamps = $entity->getTimestamps();

        return new self(
            id: $entity->getId()->getValue(),
            name: $entity->getName(),
            email: $entity->getEmail()->getValue(),
            role: $entity->getRole()->value,
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
            'role' => $this->role,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user can publish articles.
     */
    public function canPublish(): bool
    {
        return in_array($this->role, ['admin', 'editor', 'author'], true);
    }

    /**
     * Get obfuscated email for display.
     */
    public function getObfuscatedEmail(): string
    {
        $local = substr($this->email, 0, strpos($this->email, '@'));
        $domain = substr($this->email, strpos($this->email, '@') + 1);

        if (strlen($local) <= 2) {
            $obfuscated = $local[0] . '***';
        } else {
            $obfuscated = $local[0] . str_repeat('*', strlen($local) - 2) . $local[-1];
        }

        return $obfuscated . '@' . $domain;
    }
}