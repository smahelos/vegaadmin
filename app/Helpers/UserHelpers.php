<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class UserHelpers
{
    /**
     * Safely get the name of authenticated user
     *
     * @param string $default Default value if user is not authenticated
     * @return string
     */
    public static function getUserName($default = 'Guest')
    {
        if (Auth::check()) {
            return Auth::user()->name;
        }
        
        return $default;
    }
    
    /**
     * Check if authenticated user is also a Backpack admin
     *
     * @return bool
     */
    public static function isBackpackUser()
    {
        return Auth::check() && function_exists('backpack_user') && backpack_user() !== null;
    }
}