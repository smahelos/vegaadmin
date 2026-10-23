<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ApiBackpackController extends Controller
{
    /**
     * Get currently authenticated user
     *
     * @return User|null
     */
    protected function getAuthenticatedUser()
    {
        // Check all guards and return first authenticated user
        // Try backpack_auth() helper function first, which is most accurate
        if (function_exists('backpack_auth') && backpack_auth()->check()) {
            return backpack_auth()->user();
        }

        if (Auth::guard('backpack')->check()) {
            return Auth::guard('backpack')->user();
        }

        if (Auth::guard('web')->check()) {
            return Auth::guard('web')->user();
        }

        if (Auth::guard('sanctum')->check()) {
            return Auth::guard('sanctum')->user();
        }

        return null;
    }

    /**
     * Return logging context information
     *
     * @return array
     */
    protected function getLogContext(array $additionalContext = [])
    {
        $user = $this->getAuthenticatedUser();
        $session = session();

        $context = [
            'user_id' => $user ? $user->id : 'unauthenticated',
            'route' => request()->route() ? request()->route()->getName() : 'unknown',
            'path' => request()->path(),
            'is_admin' => $user && method_exists($user, 'hasRole') && $user->hasRole('admin') ? 'yes' : 'no',
            'guards' => [
                'backpack' => Auth::guard('backpack')->check() ? 'authenticated' : 'unauthenticated',
                'web' => Auth::guard('web')->check() ? 'authenticated' : 'unauthenticated',
                'sanctum' => Auth::guard('sanctum')->check() ? 'authenticated' : 'unauthenticated',
            ],
            'session_id' => $session->getId(),
            'has_backpack_session' => $session->has('backpack_session_state') ? 'yes' : 'no',
        ];

        return array_merge($context, $additionalContext);
    }
}
