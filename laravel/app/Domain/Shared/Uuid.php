<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use JsonException;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;

/**
 * UUID Value Object for unique identifiers.
 *
 * Wraps Ramsey UUID library for type-safe UUID handling.
 */
final class Uuid extends ValueObject
{
    private function __construct(
        private readonly UuidInterface $value
    ) {}

    /**
     * Create a new UUID from string.
     *
     * @throws RuntimeException If the string is not a valid UUID
     */
    public static function fromString(string $value): self
    {
        if (!RamseyUuid::isValid($value)) {
            throw new RuntimeException(sprintf('Invalid UUID: "%s"', $value));
        }

        return new self(RamseyUuid::fromString($value));
    }

    /**
     * Generate a new random UUID (v4).
     */
    public static function generate(): self
    {
        return new self(RamseyUuid::uuid4());
    }

    /**
     * Create UUID from Ramsey UUID interface.
     */
    public static function fromRamsey(UuidInterface $uuid): self
    {
        return new self($uuid);
    }

    /**
     * Check equality with another UUID.
     */
    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    /**
     * Get the string representation of the UUID.
     */
    public function getValue(): string
    {
        return $this->value->toString();
    }

    /**
     * Get the Ramsey UUID interface.
     */
    public function getRamsey(): UuidInterface
    {
        return $this->value;
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return ['uuid' => $this->getValue()];
    }

    /**
     * Unserialize for Laravel Queue support.
     *
     * @param array{value: string} $data
     */
    public function __unserialize(array $data): void
    {
        $this->value = RamseyUuid::fromString($data['value']);
    }
}
