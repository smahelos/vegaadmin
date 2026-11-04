<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ApiBackpackController extends Controller
{
    /**
     * Get currently authenticated user
     */
    protected function getAuthenticatedUser(): ?User
    {
        // Check all guards and return first authenticated user
        // Try backpack_auth() helper function first, which is most accurate
        $auth = backpack_auth();
        if ($auth->check()) {
            return $auth->user();
        }

        $guard = Auth::guard('backpack');
        if ($guard->check()) {
            return $guard->user();
        }

        $guard = Auth::guard('web');
        if ($guard->check()) {
            return $guard->user();
        }

        $guard = Auth::guard('sanctum');
        if ($guard->check()) {
            return $guard->user();
        }

        return null;
    }

    /**
     * Return logging context information
     */
    protected function getLogContext(array $additionalContext = []): array
    {
        $user = $this->getAuthenticatedUser();
        $session = session();
        $request = request();
        $route = $request->route();
        $backpackGuard = Auth::guard('backpack');
        $webGuard = Auth::guard('web');
        $sanctumGuard = Auth::guard('sanctum');

        $context = [
            'user_id' => $user ? $user->id : 'unauthenticated',
            'route' => $route ? $route->getName() : 'unknown',
            'path' => $request->path(),
            'is_admin' => $user && method_exists($user, 'hasRole') && $user->hasRole('admin') ? 'yes' : 'no',
            'guards' => [
                'backpack' => $backpackGuard->check() ? 'authenticated' : 'unauthenticated',
                'web' => $webGuard->check() ? 'authenticated' : 'unauthenticated',
                'sanctum' => $sanctumGuard->check() ? 'authenticated' : 'unauthenticated',
            ],
            'session_id' => $session->getId(),
            'has_backpack_session' => $session->has('backpack_session_state') ? 'yes' : 'no',
        ];

        return array_merge($context, $additionalContext);
    }
}
