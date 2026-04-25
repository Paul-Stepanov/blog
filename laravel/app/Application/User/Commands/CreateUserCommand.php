<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Domain\Contact\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserRole;
use App\Infrastructure\Http\Requests\Admin\UserRequest;

/**
 * Command to create a new user.
 *
 * Immutable command object for CQRS pattern.
 */
final readonly class CreateUserCommand
{
    /**
     * @param string $name User name
     * @param Email $email User email
     * @param string $password Hashed password
     * @param UserRole $role User role
     */
    public function __construct(
        public string $name,
        public Email $email,
        public string $password,
        public UserRole $role,
    ) {}

    /**
     * Create command from Form Request.
     *
     * Handles transformation from HTTP layer to Application layer.
     */
    public static function fromRequest(UserRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: Email::fromString($request->validated('email')),
            password: $request->validated('password'), // Already hashed in Form Request
            role: UserRole::from($request->validated('role', UserRole::EDITOR->value)),
        );
    }
}