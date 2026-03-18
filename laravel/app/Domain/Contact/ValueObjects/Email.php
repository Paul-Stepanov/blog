<?php

declare(strict_types=1);

namespace App\Domain\Contact\ValueObjects;

use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\ValueObject;

/**
 * Email Value Object.
 *
 * Represents a validated email address.
 */
final class Email extends ValueObject
{
    private readonly string $value;

    private function __construct(string $value)
    {
        $this->validateProperty($value);
        $this->value = strtolower($value);
    }

    /**
     * Create from string.
     *
     * @throws ValidationException
     */
    public static function fromString(string $value): self
    {
        return new self(trim($value));
    }

    /**
     * Validate email format.
     *
     * @throws ValidationException
     */
    protected function validate(mixed $value): void
    {
        if (!is_string($value)) {
            throw ValidationException::forField('email', 'Email must be a string');
        }

        if (empty($value)) {
            throw ValidationException::forField('email', 'Email cannot be empty');
        }

        if (strlen($value) > 254) {
            throw ValidationException::forField('email', 'Email cannot exceed 254 characters');
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::forField('email', sprintf('Invalid email format: "%s"', $value));
        }
    }

    /**
     * Get the local part (before @).
     */
    public function getLocalPart(): string
    {
        return substr($this->value, 0, strpos($this->value, '@'));
    }

    /**
     * Get the domain part (after @).
     */
    public function getDomain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    /**
     * Check if email is from a specific domain.
     */
    public function isFromDomain(string $domain): bool
    {
        return $this->getDomain() === strtolower($domain);
    }

    /**
     * Get obfuscated email for display (e.g., j***n@example.com).
     */
    public function getObfuscated(): string
    {
        $local = $this->getLocalPart();
        $domain = $this->getDomain();

        if (strlen($local) <= 2) {
            $obfuscated = $local[0] . '***';
        } else {
            $obfuscated = $local[0] . str_repeat('*', strlen($local) - 2) . $local[-1];
        }

        return $obfuscated . '@' . $domain;
    }

    /**
     * Check equality with another Email.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Get the email address.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return ['email' => $this->value];
    }
}