<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObjects;

use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\ValueObject;

/**
 * Password Value Object.
 *
 * Represents a hashed password with validation for plain passwords.
 */
final class Password extends ValueObject
{
    private readonly string $hashedValue;

    private function __construct(string $hashedValue)
    {
        $this->hashedValue = $hashedValue;
    }

    /**
     * Create from a plain text password (will be hashed).
     *
     * @throws ValidationException
     */
    public static function fromPlain(string $plainPassword): self
    {
        self::validatePlain($plainPassword);

        return new self(password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]));
    }

    /**
     * Create from an already hashed password.
     */
    public static function fromHash(string $hashedValue): self
    {
        return new self($hashedValue);
    }

    /**
     * Validate plain password strength.
     *
     * @throws ValidationException
     */
    private static function validatePlain(string $password): void
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        if (strlen($password) > 72) {
            $errors[] = 'Password cannot exceed 72 characters (bcrypt limit)';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, 'Password validation failed');
        }
    }

    /**
     * Verify a plain password against this hashed password.
     */
    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->hashedValue);
    }

    /**
     * Check if the password needs to be rehashed.
     */
    public function needsRehash(): bool
    {
        return password_needs_rehash($this->hashedValue, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Check equality with another Password.
     */
    public function equals(self $other): bool
    {
        return $this->hashedValue === $other->hashedValue;
    }

    /**
     * Get the hashed password.
     */
    public function getValue(): string
    {
        return $this->hashedValue;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        // Never expose the actual hash in JSON
        return ['password' => '***'];
    }
}