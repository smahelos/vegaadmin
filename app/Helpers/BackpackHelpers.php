<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('backpack_auth')) {
    /**
     * Get authentication manager instance for Backpack guard
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard|\Illuminate\Contracts\Auth\Guard
     */
    function backpack_auth()
    {
        return Auth::guard(backpack_guard_name());
    }
}

if (!function_exists('backpack_guard_name')) {
    /**
     * Get the guard name used for Backpack
     *
     * @return string
     */
    function backpack_guard_name(): string
    {
        return config('backpack.base.guard') ?? config('auth.defaults.guard');
    }
}

if (!function_exists('backpack_user')) {
    /**
     * Get the currently authenticated user in Backpack
     *
     * @return \App\Models\User|null
     */
    function backpack_user(): ?\App\Models\User
    {
        return backpack_auth()->user();
    }
}

if (!function_exists('backpack_url')) {
    /**
     * Create URL with Backpack prefix
     *
     * @param string $path
     * @return string
     */
    function backpack_url(?string $path = null): string
    {
        $prefix = config('backpack.base.route_prefix', 'admin');
        
        if (empty($path)) {
            return url($prefix);
        }

        return url($prefix.'/'.$path);
    }
}

if (!function_exists('backpack_pro')) {
    /**
     * Get authentication manager instance for Backpack guard
     *
     * @return bool
     */
    function backpack_pro(): bool
    {
        return true;
    }
}
