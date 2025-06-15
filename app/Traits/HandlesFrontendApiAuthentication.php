<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Frontend API authentication trait
 * Only works with web guard - never mixes with backpack
 */
trait HandlesFrontendApiAuthentication
{
    /**
     * Get currently authenticated frontend user
     * Only checks web guard
     *
     * @return \App\Models\User|null
     */
    protected function getFrontendUser()
    {
        if (!Auth::guard('web')->check()) {
            Log::debug('No frontend user authenticated');
            return null;
        }

        $user = Auth::guard('web')->user();
        Log::debug('Frontend user authenticated', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
        
        return $user;
    }

    /**
     * Check if frontend user has specific permission
     *
     * @param string $permission
     * @return bool
     */
    protected function frontendUserHasPermission(string $permission): bool
    {
        $user = $this->getFrontendUser();
        
        if (!$user) {
            return false;
        }

        return $user->can($permission);
    }

    /**
     * Check if frontend user has any of the specified permissions
     *
     * @param array $permissions
     * @return bool
     */
    protected function frontendUserHasAnyPermission(array $permissions): bool
    {
        $user = $this->getFrontendUser();
        
        if (!$user) {
            return false;
        }

        return $user->hasAnyPermission($permissions);
    }

    /**
     * Get logging context for frontend API requests
     *
     * @param array $additionalContext
     * @return array
     */
    protected function getFrontendApiLogContext(array $additionalContext = []): array
    {
        $user = $this->getFrontendUser();
        
        $context = [
            'guard' => 'web',
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'route' => request()->route() ? request()->route()->getName() : 'unknown',
            'path' => request()->path(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
        ];

        if ($user) {
            $context['user_roles'] = $user->getRoleNames()->toArray();
            $context['user_permissions'] = $user->getAllPermissions()->pluck('name')->toArray();
        }

        return array_merge($context, $additionalContext);
    }

    /**
     * Return unauthorized response for frontend
     *
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function frontendUnauthorizedResponse(string $message = null, int $statusCode = 401)
    {
        $message = $message ?: __('users.auth.unauthorized');
        
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'error' => $message,
                'code' => $statusCode
            ], $statusCode);
        }
        
        return redirect()->guest(route('login'));
    }
}
