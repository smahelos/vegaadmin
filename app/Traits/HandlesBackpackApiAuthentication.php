<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Backpack API authentication trait
 * Only works with backpack guard - never mixes with web
 */
trait HandlesBackpackApiAuthentication
{
    /**
     * Get currently authenticated backpack user
     * Only checks backpack guard
     *
     * @return \App\Models\User|null
     */
    protected function getBackpackUser()
    {
        if (!function_exists('backpack_auth') || !backpack_auth()->check()) {
            Log::debug('No backpack user authenticated');
            return null;
        }

        $user = backpack_auth()->user();
        Log::debug('Backpack user authenticated', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
        
        return $user;
    }

    /**
     * Check if backpack user has specific permission
     *
     * @param string $permission
     * @return bool
     */
    protected function backpackUserHasPermission(string $permission): bool
    {
        $user = $this->getBackpackUser();
        
        if (!$user) {
            Log::debug('No user found for permission check', ['permission' => $permission]);
            return false;
        }

        // Debug information
        $guardName = backpack_guard_name();
        $userRoles = $user->getRoleNames()->toArray();
        $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
        
        Log::debug('Permission check details', [
            'permission' => $permission,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'current_guard' => $guardName,
            'user_roles' => $userRoles,
            'user_permissions' => $userPermissions,
            'has_permission_backpack' => $user->hasPermissionTo($permission, 'backpack'),
            'can_permission' => $user->can($permission),
        ]);

        // Use the current guard for permission checking
        return $user->hasPermissionTo($permission, $guardName);
    }

    /**
     * Check if backpack user has any of the specified permissions
     *
     * @param array $permissions
     * @return bool
     */
    protected function backpackUserHasAnyPermission(array $permissions): bool
    {
        $user = $this->getBackpackUser();
        
        if (!$user) {
            return false;
        }

        return $user->hasAnyPermission($permissions);
    }

    /**
     * Get logging context for backpack API requests
     *
     * @param array $additionalContext
     * @return array
     */
    protected function getBackpackApiLogContext(array $additionalContext = []): array
    {
        $user = $this->getBackpackUser();
        
        $context = [
            'guard' => 'backpack',
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
     * Return unauthorized response for backpack
     *
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function backpackUnauthorizedResponse(string $message = null, int $statusCode = 401)
    {
        $message = $message ?: __('backpack::base.unauthorized');
        
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'error' => $message,
                'code' => $statusCode
            ], $statusCode);
        }
        
        return redirect()->guest(backpack_url('login'));
    }
}
