<?php

declare(strict_types=1);

namespace App\Application\User\Services;

use App\Application\User\Commands\CreateUserCommand;
use App\Application\User\Commands\UpdateUserCommand;
use App\Application\User\DTOs\UserDTO;
use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\PaginatedResult;
use App\Domain\Shared\Uuid;
use App\Domain\User\Entities\User;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\User\ValueObjects\Password;
use App\Domain\User\ValueObjects\UserRole;
use Illuminate\Support\Facades\DB;

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
            ->map(fn (User $user) => UserDTO::fromEntity($user));
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
            ->map(fn (User $user) => UserDTO::fromEntity($user));
    }

    /**
     * Search users by name or email.
     *
     * @return PaginatedResult<UserDTO>
     */
    public function searchUsers(string $query, int $page = 1, int $perPage = 20): PaginatedResult
    {
        return $this->userRepository->search($query, $page, $perPage)
            ->map(fn (User $user) => UserDTO::fromEntity($user));
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
     *
     * @throws ValidationException On self-escalation or last-admin invariant violation
     */
    public function updateUser(UpdateUserCommand $command): ?UserDTO
    {
        $user = $this->userRepository->findById(Uuid::fromString($command->userId));

        if ($user === null) {
            return null;
        }

        // Invariant: a user must not change their own role (self-escalation guard).
        if ($command->role !== null && $command->actorId === $command->userId) {
            throw ValidationException::forField(
                'role',
                'You cannot change your own role.'
            );
        }

        $demotesAdmin = $command->role !== null
            && $user->getRole()->isAdmin()
            && ! $command->role->isAdmin();

        if ($demotesAdmin) {
            // Atomic last-admin check under a row lock: count + apply + save
            // must run in one transaction to close the demotion race window.
            DB::transaction(function () use ($user, $command): void {
                if ($this->userRepository->countAdmins() <= 1) {
                    throw ValidationException::forField(
                        'role',
                        'Cannot remove the last administrator.'
                    );
                }

                $this->applyUserUpdates($user, $command);
                $this->userRepository->save($user);
            });
        } else {
            $this->applyUserUpdates($user, $command);
            $this->userRepository->save($user);
        }

        return UserDTO::fromEntity($user);
    }

    /**
     * Apply the updatable fields from the command to the entity.
     */
    private function applyUserUpdates(User $user, UpdateUserCommand $command): void
    {
        if ($command->name !== null) {
            $user->updateProfile($command->name);
        }

        if ($command->email !== null) {
            $user->changeEmail($command->email);
        }

        if ($command->password !== null) {
            $user->changePassword(Password::fromHash($command->password));
        }

        if ($command->role !== null) {
            $user->changeRole($command->role);
        }
    }

    /**
     * Delete a user.
     *
     * @throws ValidationException When deleting the last administrator
     */
    public function deleteUser(string $id): bool
    {
        $userId = Uuid::fromString($id);
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            return false;
        }

        // Invariant: never delete the last remaining admin.
        if ($user->getRole()->isAdmin()) {
            DB::transaction(function () use ($userId): void {
                if ($this->userRepository->countAdmins() <= 1) {
                    throw ValidationException::forField(
                        'role',
                        'Cannot delete the last administrator.'
                    );
                }

                $this->userRepository->delete($userId);
            });
        } else {
            $this->userRepository->delete($userId);
        }

        return true;
    }
}
