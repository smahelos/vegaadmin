<?php

namespace App\Models;

use Backpack\PermissionManager\app\Models\Permission as BasePermission;
use Illuminate\Support\Facades\Artisan;

class Permission extends BasePermission
{
    /**
     * Boot method to clear cache when Permission is saved/deleted
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear cache when Permission is saved or deleted
        static::saved(function (): void {
            Artisan::call('cache:clear');
        });

        static::deleted(function (): void {
            Artisan::call('cache:clear');
        });
    }
}
