<?php

declare(strict_types=1);

namespace App\Application\User\Services;

use App\Application\User\Commands\{CreateUserCommand, UpdateUserCommand};
use App\Application\User\DTOs\UserDTO;
use App\Domain\User\Entities\User;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\User\ValueObjects\{Password, UserRole};
use App\Domain\Shared\PaginatedResult;
use App\Domain\Shared\Uuid;

/**
 * User Application Service.
 *
 * Orchestrates user-related use cases by coordinating
 * domain objects and repository operations.
 */
final readonly class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Get all users with pagination.
     *
     * @return PaginatedResult<UserDTO>
     */
    public function getAllUsers(int $page = 1, int $perPage = 20): PaginatedResult
    {
        return $this->userRepository->findAll($page, $perPage)
            ->map(fn(User $user) => UserDTO::fromEntity($user));
    }

    /**
     * Get user by ID.
     */
    public function getUserById(string $id): ?UserDTO
    {
        $user = $this->userRepository->findById(Uuid::fromString($id));

        if ($user === null) {
            return null;
        }

        return UserDTO::fromEntity($user);
    }

    /**
     * Get users by role.
     *
     * @return PaginatedResult<UserDTO>
     */
    public function getUsersByRole(UserRole $role, int $page = 1, int $perPage = 20): PaginatedResult
    {
        return $this->userRepository->findByRole($role, $page, $perPage)
            ->map(fn(User $user) => UserDTO::fromEntity($user));
    }

    /**
     * Search users by name or email.
     *
     * @return PaginatedResult<UserDTO>
     */
    public function searchUsers(string $query, int $page = 1, int $perPage = 20): PaginatedResult
    {
        return $this->userRepository->search($query, $page, $perPage)
            ->map(fn(User $user) => UserDTO::fromEntity($user));
    }

    /**
     * Create a new user.
     */
    public function createUser(CreateUserCommand $command): UserDTO
    {
        $user = User::create(
            id: Uuid::generate(),
            name: $command->name,
            email: $command->email,
            password: Password::fromHash($command->password),
            role: $command->role,
        );

        $this->userRepository->save($user);

        return UserDTO::fromEntity($user);
    }

    /**
     * Update an existing user.
     */
    public function updateUser(UpdateUserCommand $command): ?UserDTO
    {
        $user = $this->userRepository->findById(Uuid::fromString($command->userId));

        if ($user === null) {
            return null;
        }

        // Update name if provided
        if ($command->name !== null) {
            $user->updateProfile($command->name);
        }

        // Update email if provided
        if ($command->email !== null) {
            $user->changeEmail($command->email);
        }

        // Update password if provided
        if ($command->password !== null) {
            $user->changePassword(Password::fromHash($command->password));
        }

        // Update role if provided
        if ($command->role !== null) {
            $user->changeRole($command->role);
        }

        $this->userRepository->save($user);

        return UserDTO::fromEntity($user);
    }

    /**
     * Delete a user.
     */
    public function deleteUser(string $id): bool
    {
        $userId = Uuid::fromString($id);
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            return false;
        }

        $this->userRepository->delete($userId);

        return true;
    }
}