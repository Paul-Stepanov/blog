<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use JsonException;
use JsonSerializable;

/**
 * Base Value Object class for Domain-Driven Design.
 *
 * Value Objects are objects without identity.
 * They are defined by their attributes, not by a unique ID.
 */
abstract class ValueObject implements JsonSerializable
{
    /**
     * Validates a property value if a validate method is defined.
     *
     * @param mixed $value Value to validate
     */
    final protected function validateProperty(mixed $value): void
    {
        if (method_exists($this, 'validate')) {
            $this->validate($value);
        }
    }

    /**
     * Returns a string representation of the value object.
     *
     * @throws JsonException
     */
    public function __toString(): string
    {
        return json_encode($this->jsonSerialize(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    /**
     * Returns a JSON serializable array representation.
     *
     * @return array<string, mixed>
     */
    abstract public function jsonSerialize(): array;

    /**
     * Returns the raw value of the value object.
     */
    abstract public function getValue(): mixed;

    /**
     * Serialize for Laravel Queue support.
     *
     * @return array{value: mixed}
     */
    public function __serialize(): array
    {
        return ['value' => $this->getValue()];
    }
}