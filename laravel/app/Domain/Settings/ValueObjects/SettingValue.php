<?php

declare(strict_types=1);

namespace App\Domain\Settings\ValueObjects;

use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\ValueObject;

/**
 * Setting Value Value Object.
 *
 * Represents a typed setting value with automatic type coercion.
 */
final class SettingValue extends ValueObject
{
    private readonly mixed $value;
    private readonly string $type;

    private const TYPE_STRING = 'string';
    private const TYPE_INTEGER = 'integer';
    private const TYPE_FLOAT = 'float';
    private const TYPE_BOOLEAN = 'boolean';
    private const TYPE_JSON = 'json';

    private function __construct(mixed $value, string $type)
    {
        $this->type = $type;
        $this->value = $this->castToType($value, $type);
    }

    /**
     * Create from raw value with type inference.
     */
    public static function fromMixed(mixed $value): self
    {
        $type = self::inferType($value);
        return new self($value, $type);
    }

    /**
     * Create from string value (for database storage).
     */
    public static function fromString(string $value, string $type): self
    {
        return new self($value, $type);
    }

    /**
     * Create a string value.
     */
    public static function string(string $value): self
    {
        return new self($value, self::TYPE_STRING);
    }

    /**
     * Create an integer value.
     */
    public static function integer(int $value): self
    {
        return new self($value, self::TYPE_INTEGER);
    }

    /**
     * Create a float value.
     */
    public static function float(float $value): self
    {
        return new self($value, self::TYPE_FLOAT);
    }

    /**
     * Create a boolean value.
     */
    public static function boolean(bool $value): self
    {
        return new self($value, self::TYPE_BOOLEAN);
    }

    /**
     * Create a JSON value from array.
     */
    public static function json(array $value): self
    {
        return new self($value, self::TYPE_JSON);
    }

    /**
     * Infer type from value.
     */
    private static function inferType(mixed $value): string
    {
        if (is_bool($value)) {
            return self::TYPE_BOOLEAN;
        }

        if (is_int($value)) {
            return self::TYPE_INTEGER;
        }

        if (is_float($value)) {
            return self::TYPE_FLOAT;
        }

        if (is_array($value)) {
            return self::TYPE_JSON;
        }

        return self::TYPE_STRING;
    }

    /**
     * Cast value to specified type.
     *
     * @throws ValidationException
     */
    private function castToType(mixed $value, string $type): mixed
    {
        return match ($type) {
            self::TYPE_STRING => is_string($value) ? $value : (string) $value,
            self::TYPE_INTEGER => is_int($value) ? $value : (int) $value,
            self::TYPE_FLOAT => is_float($value) ? $value : (float) $value,
            self::TYPE_BOOLEAN => $this->castToBoolean($value),
            self::TYPE_JSON => $this->castToJson($value),
            default => throw ValidationException::forField('type', sprintf('Unknown type: "%s"', $type)),
        };
    }

    /**
     * Cast value to boolean.
     *
     * @throws ValidationException
     */
    private function castToBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return match (strtolower($value)) {
                'true', '1', 'yes', 'on' => true,
                'false', '0', 'no', 'off', '' => false,
                default => throw ValidationException::forField('value', 'Cannot cast to boolean'),
            };
        }

        return (bool) $value;
    }

    /**
     * Cast value to JSON (array).
     *
     * @throws ValidationException
     */
    private function castToJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw ValidationException::forField('value', 'Invalid JSON: ' . json_last_error_msg());
            }

            return $decoded;
        }

        throw ValidationException::forField('value', 'Cannot cast to JSON');
    }

    /**
     * Get value as string (for database storage).
     *
     * @throws ValidationException If JSON encoding fails
     */
    public function toString(): string
    {
        return match ($this->type) {
            self::TYPE_BOOLEAN => $this->value ? 'true' : 'false',
            self::TYPE_JSON => $this->encodeJson(),
            default => (string) $this->value,
        };
    }

    /**
     * Encode JSON value to string.
     *
     * @throws ValidationException If JSON encoding fails
     */
    private function encodeJson(): string
    {
        try {
            return json_encode($this->value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $e) {
            throw ValidationException::forField('value', 'Failed to encode JSON: ' . $e->getMessage());
        }
    }

    /**
     * Get the raw typed value.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Get the type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Check if value is a string.
     */
    public function isString(): bool
    {
        return $this->type === self::TYPE_STRING;
    }

    /**
     * Check if value is an integer.
     */
    public function isInteger(): bool
    {
        return $this->type === self::TYPE_INTEGER;
    }

    /**
     * Check if value is a boolean.
     */
    public function isBoolean(): bool
    {
        return $this->type === self::TYPE_BOOLEAN;
    }

    /**
     * Check if value is JSON (array).
     */
    public function isJson(): bool
    {
        return $this->type === self::TYPE_JSON;
    }

    /**
     * Get value as integer.
     *
     * @throws ValidationException
     */
    public function asInteger(): int
    {
        if ($this->type !== self::TYPE_INTEGER) {
            throw ValidationException::forField('value', 'Value is not an integer');
        }

        return $this->value;
    }

    /**
     * Get value as boolean.
     *
     * @throws ValidationException
     */
    public function asBoolean(): bool
    {
        if ($this->type !== self::TYPE_BOOLEAN) {
            throw ValidationException::forField('value', 'Value is not a boolean');
        }

        return $this->value;
    }

    /**
     * Get value as array.
     *
     * @throws ValidationException
     */
    public function asArray(): array
    {
        if ($this->type !== self::TYPE_JSON) {
            throw ValidationException::forField('value', 'Value is not JSON');
        }

        return $this->value;
    }

    /**
     * Check equality with another SettingValue.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value && $this->type === $other->type;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'value' => $this->value,
            'type' => $this->type,
        ];
    }
}