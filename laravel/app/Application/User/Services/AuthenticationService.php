<?php

declare(strict_types=1);

namespace App\Application\User\Services;

use App\Application\User\DTOs\{AuthRequest, UserDTO};
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\Shared\Uuid;

/**
 * Authentication Application Service.
 *
 * Handles user authentication operations.
 */
final readonly class AuthenticationService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Authenticate user by credentials.
     *
     * @param AuthRequest $request Login credentials
     * @return UserDTO|null Authenticated user or null if failed
     */
    public function login(AuthRequest $request): ?UserDTO
    {
        $user = $this->userRepository->findByEmail($request->email->getValue());

        if ($user === null) {
            return null;
        }

        if (!$user->verifyPassword($request->password)) {
            return null;
        }

        return UserDTO::fromEntity($user);
    }

    /**
     * Get user by ID.
     *
     * @param string $userId User UUID string
     * @return UserDTO|null User or null if not found
     */
    public function getUserById(string $userId): ?UserDTO
    {
        $uuid = Uuid::fromString($userId);
        $user = $this->userRepository->findById($uuid);

        if ($user === null) {
            return null;
        }

        return UserDTO::fromEntity($user);
    }

    /**
     * Verify user exists and is active.
     *
     * @param string $userId User UUID string
     * @return bool True if user exists and can login
     */
    public function canAuthenticate(string $userId): bool
    {
        $uuid = Uuid::fromString($userId);
        $user = $this->userRepository->findById($uuid);

        return $user !== null;
    }
}