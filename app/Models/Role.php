<?php

namespace App\Models;

use Backpack\PermissionManager\app\Models\Role as BaseRole;
use Illuminate\Support\Facades\Artisan;

class Role extends BaseRole
{
    /**
     * Boot method to clear cache when Role is saved/deleted
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear cache when Role is saved or deleted
        static::saved(function (): void {
            Artisan::call('cache:clear');
        });

        static::deleted(function (): void {
            Artisan::call('cache:clear');
        });
    }
}
