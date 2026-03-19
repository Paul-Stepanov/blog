<?php

declare(strict_types=1);

namespace App\Application\User\DTOs;

use App\Domain\Contact\ValueObjects\Email;
use App\Domain\Shared\Exceptions\ValidationException;

/**
 * Authentication Request DTO.
 *
 * Immutable data transfer object for login requests.
 * Uses Email VO for validation.
 */
final readonly class AuthRequest
{
    /**
     * @param Email $email User email (VO - validation)
     * @param string $password Plain text password (validated in Service)
     */
    public function __construct(
        public Email $email,
        public string $password,
    ) {}

    /**
     * Create from primitive values.
     *
     * @throws ValidationException
     * 
     */
    public static function fromPrimitives(string $email, string $password): self
    {
        return new self(
            email: Email::fromString($email),
            password: $password,
        );
    }
}