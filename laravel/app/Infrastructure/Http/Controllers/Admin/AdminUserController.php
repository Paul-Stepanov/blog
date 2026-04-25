<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Application\User\Commands\CreateUserCommand;
use App\Application\User\Commands\UpdateUserCommand;
use App\Application\User\Services\UserService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Admin\UserRequest;
use App\Infrastructure\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin User Management Controller.
 *
 * Handles CRUD operations for users in the admin panel.
 */
final class AdminUserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Get all users.
     *
     * @OA\Get(
     *     path="/api/admin/users",
     *     summary="Get all users (admin)",
     *     tags={"Admin Users"},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of users")
     * )
     */
    public function getAllUsers(Request $request): JsonResponse
    {
        $page = max((int) $request->input('page', 1), 1);
        $perPage = min((int) $request->input('per_page', 20), 100);

        $result = $this->userService->getAllUsers($page, $perPage);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($result->items),
            'meta' => [
                'current_page' => $result->page,
                'last_page' => $result->lastPage,
                'per_page' => $result->perPage,
                'total' => $result->total,
                'from' => ($result->page - 1) * $result->perPage + 1,
                'to' => min($result->page * $result->perPage, $result->total),
            ],
        ]);
    }

    /**
     * Get a single user by ID.
     *
     * @OA\Get(
     *     path="/api/admin/users/{id}",
     *     summary="Get user by ID (admin)",
     *     tags={"Admin Users"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="User details"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function getUserById(string $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        if ($user === null) {
            return response()->json([
                'success' => false,
                'error' => 'user_not_found',
                'message' => "User not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Create a new user.
     *
     * @OA\Post(
     *     path="/api/admin/users",
     *     summary="Create user",
     *     tags={"Admin Users"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name", "email", "password"},
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="role", type="string", enum={"admin","editor","author"})
     *     )),
     *     @OA\Response(response=201, description="User created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createUser(UserRequest $request): JsonResponse
    {
        $command = CreateUserCommand::fromRequest($request);
        $user = $this->userService->createUser($command);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Update an existing user.
     *
     * @OA\Put(
     *     path="/api/admin/users/{id}",
     *     summary="Update user",
     *     tags={"Admin Users"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="role", type="string", enum={"admin","editor","author"})
     *     )),
     *     @OA\Response(response=200, description="User updated"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function updateUser(UserRequest $request, string $id): JsonResponse
    {
        $command = UpdateUserCommand::fromRequest($request, $id);
        $user = $this->userService->updateUser($command);

        if ($user === null) {
            return response()->json([
                'success' => false,
                'error' => 'user_not_found',
                'message' => "User not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Delete a user.
     *
     * @OA\Delete(
     *     path="/api/admin/users/{id}",
     *     summary="Delete user",
     *     tags={"Admin Users"},
     *     @OA\Response(response=200, description="User deleted"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function deleteUser(string $id): JsonResponse
    {
        $deleted = $this->userService->deleteUser($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'error' => 'user_not_found',
                'message' => "User not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }
}