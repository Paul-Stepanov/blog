<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ensures the authenticated user has admin privileges.
 *
 * Defense-in-depth layer: even with a valid Sanctum session, non-admin users
 * (editor, author) are denied access to the entire admin API surface.
 */
final class EnsureUserIsAdmin
{
    /**
     * Reject any authenticated user that is not an admin.
     *
     * @param  Request  $request  Incoming HTTP request
     * @return JsonResponse|mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user instanceof UserModel || ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'error' => 'forbidden',
                'message' => 'This action requires admin privileges.',
            ], 403);
        }

        return $next($request);
    }
}
