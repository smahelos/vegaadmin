<?php

namespace App\Models;

use Backpack\PermissionManager\app\Models\Permission as BasePermission;
use Illuminate\Support\Facades\Artisan;

class Permission extends BasePermission
{
    /**
     * Boot method to clear cache when Permission is saved/deleted
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when Permission is saved or deleted
        static::saved(function () {
            Artisan::call('cache:clear');
        });

        static::deleted(function () {
            Artisan::call('cache:clear');
        });
    }
}
