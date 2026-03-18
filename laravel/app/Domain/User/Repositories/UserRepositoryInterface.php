<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories;

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
     * Find user by ID.
     */
    public function findById(Uuid $id): ?User;

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find user by email for authentication (includes password).
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