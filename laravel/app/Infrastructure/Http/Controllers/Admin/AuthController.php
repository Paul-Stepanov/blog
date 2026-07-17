<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Application\User\DTOs\AuthRequest;
use App\Application\User\Services\AuthenticationService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Admin\LoginRequest;
use App\Infrastructure\Http\Resources\UserResource;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

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
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"email", "password"},
     *
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string", format="password")
     *     )),
     *
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

        // Login gate: only admins may enter the admin area.
        // AuthenticationService stays a pure credential check; role is an admin concern.
        if (! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'error' => 'forbidden',
                'message' => 'Access to the admin panel requires admin privileges.',
            ], 403);
        }

        $userModel = UserModel::query()->where('uuid', $user->id)->first();

        if ($userModel !== null) {
            Auth::login($userModel);
        }

        $request->session()->regenerate();

        // Clear the per-account brute-force counter after a successful admin login.
        RateLimiter::clear($this->loginThrottleKey($request));

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Build the throttle key matching the named 'login' limiter (see AppServiceProvider).
     *
     * Laravel hashes named-limiter keys as md5(<limiter-name>.<by-key>) by default,
     * so we mirror that here for a precise clear().
     */
    private function loginThrottleKey(LoginRequest $request): string
    {
        $byKey = $request->validated('email').'|'.$request->ip();

        return md5('login'.$byKey);
    }

    /**
     * Logout admin user.
     *
     * @OA\Post(
     *     path="/api/admin/auth/logout",
     *     summary="Admin logout",
     *     tags={"Admin Auth"},
     *
     *     @OA\Response(response=200, description="Logout successful")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

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
     *
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

        $userDTO = $this->authService->getUserById($user->uuid);

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
