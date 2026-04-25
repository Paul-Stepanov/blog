<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Domain\Contact\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserRole;
use App\Infrastructure\Http\Requests\Admin\UserRequest;

/**
 * Command to update an existing user.
 *
 * Immutable command object for CQRS pattern.
 * All parameters are optional - only provided fields will be updated.
 */
final readonly class UpdateUserCommand
{
    /**
     * @param string $userId User ID (primitive for parsing)
     * @param string|null $name New name
     * @param Email|null $email New email
     * @param string|null $password New hashed password
     * @param UserRole|null $role New role
     */
    public function __construct(
        public string $userId,
        public ?string $name = null,
        public ?Email $email = null,
        public ?string $password = null,
        public ?UserRole $role = null,
    ) {}

    /**
     * Create command from Form Request.
     *
     * Handles transformation from HTTP layer to Application layer.
     */
    public static function fromRequest(UserRequest $request, string $id): self
    {
        return new self(
            userId: $id,
            name: $request->validated('name'),
            email: $request->validated('email') !== null
                ? Email::fromString($request->validated('email'))
                : null,
            password: $request->validated('password'), // Already hashed in Form Request if provided
            role: $request->validated('role') !== null
                ? UserRole::from($request->validated('role'))
                : null,
        );
    }
}