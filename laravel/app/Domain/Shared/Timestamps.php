<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use DateTimeImmutable;
use DateTimeInterface;
use JsonException;

/**
 * Value Object for handling creation and update timestamps.
 *
 * Provides immutable timestamp handling for entities.
 */
final class Timestamps extends ValueObject
{
    public function __construct(
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {}

    /**
     * Create timestamps for a new entity.
     */
    public static function now(): self
    {
        $now = new DateTimeImmutable();

        return new self($now, $now);
    }

    /**
     * Create timestamps from existing values.
     */
    public static function fromStrings(string $createdAt, string $updatedAt): self
    {
        return new self(
            new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt)
        );
    }

    /**
     * Create a new instance with updated timestamp.
     */
    public function touch(): self
    {
        return new self(
            $this->createdAt,
            new DateTimeImmutable()
        );
    }

    /**
     * Get creation timestamp.
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Get update timestamp.
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Check if the entity has been modified since creation.
     */
    public function isModified(): bool
    {
        return $this->createdAt !== $this->updatedAt;
    }

    /**
     * Format timestamps for display.
     */
    public function format(string $format = 'Y-m-d H:i:s'): array
    {
        return [
            'createdAt' => $this->createdAt->format($format),
            'updatedAt' => $this->updatedAt->format($format),
        ];
    }

    /**
     * @return array<string, string>
     * @throws JsonException
     */
    public function jsonSerialize(): array
    {
        return [
            'createdAt' => $this->createdAt->format(DateTimeInterface::ATOM),
            'updatedAt' => $this->updatedAt->format(DateTimeInterface::ATOM),
        ];
    }

    /**
     * Get the primary value (array of timestamps).
     *
     * @return array{createdAt: DateTimeImmutable, updatedAt: DateTimeImmutable}
     */
    public function getValue(): array
    {
        return [
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}