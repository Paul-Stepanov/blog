<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Application\User\DTOs\AuthRequest;
use App\Application\User\Services\AuthenticationService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Admin\LoginRequest;
use App\Infrastructure\Http\Resources\UserResource;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;

/**
 * Admin Authentication Controller.
 *
 * Handles admin authentication via Laravel Sanctum (cookie-based).
 */
final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authService,
    ) {}

    /**
     * Authenticate admin user.
     *
     * @OA\Post(
     *     path="/api/admin/auth/login",
     *     summary="Admin login",
     *     tags={"Admin Auth"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"email", "password"},
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string", format="password")
     *     )),
     *     @OA\Response(response=200, description="Login successful"),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=429, description="Too many attempts")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $authRequest = AuthRequest::fromPrimitives(
            email: $request->validated('email'),
            password: $request->validated('password'),
        );

        $user = $this->authService->login($authRequest);

        if ($user === null) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_credentials',
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // Use Laravel's built-in auth for session management
        Auth::loginUsingId($user->id);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Logout admin user.
     *
     * @OA\Post(
     *     path="/api/admin/auth/logout",
     *     summary="Admin logout",
     *     tags={"Admin Auth"},
     *     @OA\Response(response=200, description="Logout successful")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get current authenticated admin user.
     *
     * @OA\Get(
     *     path="/api/admin/user",
     *     summary="Get current admin user",
     *     tags={"Admin Auth"},
     *     @OA\Response(response=200, description="Current user data"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getCurrentUser(): JsonResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'error' => 'unauthenticated',
                'message' => 'No authenticated user.',
            ], 401);
        }

        $userDTO = $this->authService->getUserById($user->id);

        if ($userDTO === null) {
            return response()->json([
                'success' => false,
                'error' => 'user_not_found',
                'message' => 'Authenticated user not found in database.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new UserResource($userDTO),
        ]);
    }
}