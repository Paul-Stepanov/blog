<?php

declare(strict_types=1);

namespace App\Domain\User\Entities;

use App\Domain\Shared\Entity;
use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\Timestamps;
use App\Domain\Shared\Uuid;
use App\Domain\User\ValueObjects\{Password, UserRole};
use App\Domain\Contact\ValueObjects\Email;

/**
 * User Entity - Aggregate Root.
 *
 * Represents a user in the system (admin, editor, author).
 */
final class User extends Entity
{
    // Mutable properties
    private string $name;
    private Email $email;
    private Password $password;
    private UserRole $role;
    private Timestamps $timestamps;

    public function __construct(
        Uuid $id,
        string $name,
        Email $email,
        Password $password,
        UserRole $role,
        Timestamps $timestamps,
    ) {
        parent::__construct($id);

        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
        $this->timestamps = $timestamps;
    }

    /**
     * Create a new user.
     */
    public static function create(
        Uuid $id,
        string $name,
        Email $email,
        Password $password,
        UserRole $role = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            email: $email,
            password: $password,
            role: $role ?? UserRole::default(),
            timestamps: Timestamps::now(),
        );
    }

    /**
     * Reconstruct from persistence.
     */
    public static function reconstitute(
        Uuid $id,
        string $name,
        Email $email,
        Password $password,
        UserRole $role,
        Timestamps $timestamps,
    ): self {
        return new self(
            id: $id,
            name: $name,
            email: $email,
            password: $password,
            role: $role,
            timestamps: $timestamps,
        );
    }

    /**
     * Update profile information.
     *
     * @throws ValidationException
     */
    public function updateProfile(string $name): void
    {
        if (empty(trim($name))) {
            throw ValidationException::forField('name', 'Name cannot be empty');
        }

        $this->name = $name;
        $this->timestamps = $this->timestamps->touch();
    }

    /**
     * Change email.
     */
    public function changeEmail(Email $newEmail): void
    {
        $this->email = $newEmail;
        $this->timestamps = $this->timestamps->touch();
    }

    /**
     * Change password.
     */
    public function changePassword(Password $newPassword): void
    {
        $this->password = $newPassword;
        $this->timestamps = $this->timestamps->touch();
    }

    /**
     * Verify current password.
     */
    public function verifyPassword(string $plainPassword): bool
    {
        return $this->password->verify($plainPassword);
    }

    /**
     * Change role.
     *
     * @throws ValidationException
     */
    public function changeRole(UserRole $newRole): void
    {
        $this->role = $newRole;
        $this->timestamps = $this->timestamps->touch();
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user can manage other users.
     */
    public function canManageUsers(): bool
    {
        return $this->role->canManageUsers();
    }

    /**
     * Check if user can publish articles.
     */
    public function canPublish(): bool
    {
        return $this->role->canPublish();
    }
    
    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPassword(): Password
    {
        return $this->password;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getTimestamps(): Timestamps
    {
        return $this->timestamps;
    }
}