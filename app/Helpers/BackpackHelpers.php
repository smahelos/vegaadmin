<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('backpack_auth')) {
    /**
     * Resolve the configured Backpack auth guard instance.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard|\Illuminate\Contracts\Auth\Guard Auth guard for Backpack
     */
    function backpack_auth()
    {
        return Auth::guard(backpack_guard_name());
    }
}

if (!function_exists('backpack_guard_name')) {
    /**
     * Determine the guard name used for Backpack (falls back to auth default if unset).
     *
     * @return string Guard name
     */
    function backpack_guard_name(): string
    {
        return config('backpack.base.guard') ?? config('auth.defaults.guard');
    }
}

if (!function_exists('backpack_user')) {
    /**
     * Retrieve the currently authenticated Backpack user model.
     *
     * @return \App\Models\User|null Authenticated user or null
     */
    function backpack_user(): ?\App\Models\User
    {
        return backpack_auth()->user();
    }
}

if (!function_exists('backpack_url')) {
    /**
     * Build a URL under the Backpack route prefix.
     *
     * @param string|null $path Optional sub-path under the Backpack prefix
     * @return string Absolute URL
     */
    function backpack_url(?string $path = null): string
    {
        $prefix = config('backpack.base.route_prefix', 'admin');

        if ($path === null || $path === '') {
            return url($prefix);
        }

        return url($prefix.'/'.$path);
    }
}

if (!function_exists('backpack_pro')) {
    /**
     * Indicate whether Backpack Pro (or extended feature set) is enabled.
     * Placeholder always returns true; adjust when feature gating is introduced.
     *
     * @return bool
     */
    function backpack_pro(): bool
    {
        return true;
    }
}
