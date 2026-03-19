<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories;

use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\PaginatedResult;
use App\Domain\Shared\Uuid;
use App\Domain\User\Entities\User;
use App\Domain\User\ValueObjects\UserRole;

/**
 * User Repository Interface.
 *
 * Contract for user persistence operations.
 */
interface UserRepositoryInterface
{
    /**
     * Find user by ID - optional lookup.
     *
     * Use this when the user may or may not exist.
     * For mandatory lookups, use getById().
     */
    public function findById(Uuid $id): ?User;

    /**
     * Get user by ID - mandatory lookup.
     *
     * Use this when the user MUST exist by business logic.
     *
     * @throws EntityNotFoundException If user not found
     */
    public function getById(Uuid $id): User;

    /**
     * Find user by email - optional lookup.
     *
     * Use this when the user may or may not exist.
     * For mandatory lookups, use getByEmailOrFail().
     */
    public function findByEmail(string $email): ?User;

    /**
     * Get user by email - mandatory lookup.
     *
     * Use this when the user MUST exist by business logic (e.g., authentication).
     *
     * @throws EntityNotFoundException If user not found
     */
    public function getByEmailOrFail(string $email): User;

    /**
     * Find user by email for authentication (includes password).
     *
     * This method is specifically for authentication flow.
     *
     * @deprecated Use getByEmailOrFail() for mandatory, findByEmail() for optional
     */
    public function findByEmailForAuth(string $email): ?User;

    /**
     * Find all users with pagination.
     *
     * @return PaginatedResult<User>
     */
    public function findAll(int $page = 1, int $perPage = 20): PaginatedResult;

    /**
     * Find users by role.
     *
     * @return PaginatedResult<User>
     */
    public function findByRole(UserRole $role, int $page = 1, int $perPage = 20): PaginatedResult;

    /**
     * Search users by name or email.
     *
     * @return PaginatedResult<User>
     */
    public function search(string $query, int $page = 1, int $perPage = 20): PaginatedResult;

    /**
     * Get all admins.
     *
     * @return array<User>
     */
    public function getAdmins(): array;

    /**
     * Get all editors.
     *
     * @return array<User>
     */
    public function getEditors(): array;

    /**
     * Save user (create or update).
     */
    public function save(User $user): void;

    /**
     * Delete user by ID.
     */
    public function delete(Uuid $id): void;

    /**
     * Check if email exists.
     */
    public function emailExists(string $email, ?Uuid $excludeId = null): bool;

    /**
     * Count total users.
     */
    public function count(): int;

    /**
     * Count users by role.
     *
     * @return array<string, int>
     */
    public function countByRole(): array;
}