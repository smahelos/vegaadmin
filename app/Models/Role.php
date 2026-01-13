<?php

namespace App\Models;

use Backpack\PermissionManager\app\Models\Role as BaseRole;
use Illuminate\Support\Facades\Artisan;

class Role extends BaseRole
{
    /**
     * Boot method to clear cache when Role is saved/deleted
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when Role is saved or deleted
        static::saved(function () {
            Artisan::call('cache:clear');
        });

        static::deleted(function () {
            Artisan::call('cache:clear');
        });
    }
}
